<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShopifyService
{
    private $client;
    private $shopDomain;
    private $accessToken;
    private $apiVersion = '2023-10';

    public function __construct()
    {
        $this->shopDomain = config('services.shopify.shop_domain');
        $this->accessToken = config('services.shopify.access_token');
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);
    }

    private function makeRequest($endpoint, $params = [])
    {
        $url = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/{$endpoint}";
        
        $headers = [
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json'
        ];

        return $this->client->get($url, [
            'headers' => $headers,
            'query' => $params
        ]);
    }

    public function getProducts($limit = 50)
    {
        try {
            Log::info('Fetching products from Shopify:', [
                'shop_domain' => $this->shopDomain,
                'limit' => $limit
            ]);
            
            // Obtener productos con colecciones
            $response = $this->makeRequest('products.json', [
                'limit' => $limit,
                'status' => 'active',
                'fields' => 'id,title,product_type,vendor,tags,status,variants,images,handle'
            ]);

            $data = json_decode($response->getBody(), true);
            $products = $data['products'] ?? [];
            
            Log::info('Products API response:', [
                'count' => count($products),
                'sample_keys' => count($products) > 0 ? array_keys($products[0]) : [],
                'first_product_sample' => count($products) > 0 ? [
                    'title' => $products[0]['title'] ?? 'N/A',
                    'product_type' => $products[0]['product_type'] ?? null,
                    'vendor' => $products[0]['vendor'] ?? null,
                    'tags' => $products[0]['tags'] ?? null
                ] : null
            ]);
            
            return collect($products)->map(function ($product) {
                $variant = $product['variants'][0] ?? [];
                $image = $product['images'][0] ?? null;
                
                return [
                    'id' => $product['id'],
                    'name' => $product['title'],
                    'sku' => $variant['sku'] ?? 'N/A',
                    'price' => $variant['price'] ?? '0',
                    'regular_price' => $variant['compare_at_price'] ?? $variant['price'] ?? '0',
                    'sale_price' => $variant['compare_at_price'] ? $variant['price'] : null,
                    'stock_quantity' => $variant['inventory_quantity'] ?? 0,
                    'stock_status' => ($variant['inventory_quantity'] ?? 0) > 0 ? 'instock' : 'outofstock',
                    'image' => $image ? $image['src'] : null,
                    'categories' => $this->getProductCategory($product),
                    'product_type_raw' => $product['product_type'] ?? null,
                    'tags_raw' => $product['tags'] ?? null,
                    'vendor' => $product['vendor'] ?? 'N/A',
                ];
            });

        } catch (RequestException $e) {
            Log::error('Shopify API Error (Products): ' . $e->getMessage());
            throw new \Exception('Error conectando con Shopify: ' . $e->getMessage());
        }
    }

    public function getOrders($limit = 50, $days = 30)
    {
        try {
            // Probar múltiples endpoints para encontrar las órdenes
            $endpoints = [
                ['orders.json', ['limit' => $limit, 'status' => 'any']],
                ['orders.json', ['limit' => $limit]], // Sin filtro de estado
                ['draft_orders.json', ['limit' => $limit]], // Órdenes borrador
                ['orders.json', ['limit' => $limit, 'status' => 'open']],
                ['orders.json', ['limit' => $limit, 'status' => 'closed']],
                ['orders.json', ['limit' => $limit, 'fulfillment_status' => 'any']],
            ];
            
            $allOrders = [];
            foreach ($endpoints as $i => [$endpoint, $params]) {
                try {
                    $response = $this->makeRequest($endpoint, $params);
                    $data = json_decode($response->getBody(), true);
                    $orders = $data['orders'] ?? $data['draft_orders'] ?? [];
                    
                    Log::info("Testing endpoint $endpoint:", [
                        'params' => $params,
                        'count' => count($orders),
                        'sample' => count($orders) > 0 ? array_keys($orders[0]) : []
                    ]);
                    
                    if (count($orders) > 0) {
                        $allOrders = array_merge($allOrders, $orders);
                        break; // Encontramos órdenes, usar este endpoint
                    }
                } catch (\Exception $e) {
                    Log::info("Endpoint $endpoint failed: " . $e->getMessage());
                }
            }
            
            // Usar las órdenes encontradas o array vacío
            $orders = $allOrders;
            
            // Debug: Registrar resultado final
            Log::info('Shopify Orders Final Result:', [
                'total_orders' => count($orders),
                'orders_sample' => count($orders) > 0 ? array_keys($orders[0] ?? []) : []
            ]);
            
            return collect($orders)->map(function ($order) {
                $billingAddress = $order['billing_address'] ?? [];
                $customer = $order['customer'] ?? [];
                
                return [
                    'id' => $order['id'],
                    'order_number' => $order['order_number'] ?? $order['name'],
                    'status' => $this->mapShopifyStatus($order['financial_status'], $order['fulfillment_status']),
                    'total' => $order['total_price'] ?? '0',
                    'currency' => $order['currency'] ?? 'USD',
                    'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    'customer_email' => $customer['email'] ?? $billingAddress['name'] ?? 'N/A',
                    'date_created' => $order['created_at'],
                    'payment_method' => $order['gateway'] ?? 'N/A',
                    'items_count' => count($order['line_items'] ?? []),
                    'line_items' => collect($order['line_items'] ?? [])->map(function ($item) {
                        return [
                            'name' => $item['name'],
                            'quantity' => $item['quantity'],
                            'total' => $item['price'],
                            'sku' => $item['sku'] ?? 'N/A'
                        ];
                    })->toArray(),
                ];
            });

        } catch (RequestException $e) {
            Log::error('Shopify API Error (Orders): ' . $e->getMessage());
            throw new \Exception('Error conectando con Shopify: ' . $e->getMessage());
        }
    }

    public function getOrdersWithFilters($limit = 50, $filters = [])
    {
        // Primero obtener todas las órdenes usando método existente
        $allOrders = $this->getOrders(250, $filters['days'] ?? 30);
        
        // Aplicar filtros adicionales
        $filteredOrders = $allOrders->filter(function ($order) use ($filters) {
            // Filtro de rango de fechas (fechas personalizadas)
            if ($filters['period_type'] === 'custom' && ($filters['date_from'] || $filters['date_to'])) {
                $orderDate = \Carbon\Carbon::parse($order['date_created'])->format('Y-m-d');
                
                if ($filters['date_from'] && $orderDate < $filters['date_from']) {
                    return false;
                }
                
                if ($filters['date_to'] && $orderDate > $filters['date_to']) {
                    return false;
                }
            }
            
            // Filtro de búsqueda por cliente
            if (!empty($filters['customer_search'])) {
                $searchTerm = strtolower($filters['customer_search']);
                $customerName = strtolower($order['customer_name']);
                $customerEmail = strtolower($order['customer_email']);
                
                if (strpos($customerName, $searchTerm) === false && 
                    strpos($customerEmail, $searchTerm) === false) {
                    return false;
                }
            }
            
            // Filtro por estado
            if (!empty($filters['status'])) {
                if (strtolower($order['status']) !== strtolower($filters['status'])) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Aplicar límite y retornar
        return $filteredOrders->take($limit);
    }

    private function getProductCategory($product)
    {
        // Intentar product_type primero (campo principal de categoría)
        if (!empty($product['product_type'])) {
            Log::debug('Using product_type as category', ['product_type' => $product['product_type']]);
            return $product['product_type'];
        }
        
        // Intentar tags (Shopify suele usar tags para categorización)
        if (!empty($product['tags'])) {
            $tags = explode(',', $product['tags']);
            $firstTag = trim($tags[0]);
            if (!empty($firstTag)) {
                Log::debug('Using tag as category', ['tag' => $firstTag]);
                return $firstTag;
            }
        }
        
        // Crear categorías consolidadas basadas en patrones de nombres de productos
        // Esto agrupará productos similares para mejor visualización
        $productName = strtolower($product['title'] ?? '');
        
        // Categorías principales de ropa - diseñadas para agrupar múltiples productos
        if (stripos($productName, 'calcet') !== false) {
            return 'Calcetines';
        } elseif (stripos($productName, 'parka') !== false) {
            return 'Parkas';  
        } elseif (stripos($productName, 'zapatilla') !== false) {
            return 'Zapatillas';
        } elseif (stripos($productName, 'pantalon') !== false) {
            return 'Ropa y accesorios';
        } elseif (stripos($productName, 'camiseta') !== false || stripos($productName, 'camisa') !== false) {
            return 'Camisetas';
        }
        
        Log::info('Product categorization fallback', [
            'product_name' => $productName,
            'product_title_original' => $product['title'] ?? 'N/A'
        ]);
        
        // Intentar vendor como respaldo
        if (!empty($product['vendor'])) {
            return $product['vendor'];
        }
        
        // Último recurso
        return 'Sin categoría';
    }

    private function getProductCollections($productId)
    {
        try {
            $response = $this->makeRequest("products/{$productId}/collections.json");
            $data = json_decode($response->getBody(), true);
            $collections = $data['collections'] ?? [];
            
            return array_map(function($collection) {
                return $collection['title'];
            }, $collections);
            
        } catch (\Exception $e) {
            Log::debug("Failed to get collections for product {$productId}: " . $e->getMessage());
            return [];
        }
    }

    private function mapShopifyStatus($financialStatus, $fulfillmentStatus)
    {
        if ($fulfillmentStatus === 'fulfilled') {
            return 'completed';
        }
        
        return match($financialStatus) {
            'paid' => 'processing',
            'pending' => 'pending',
            'refunded' => 'refunded',
            'voided' => 'cancelled',
            default => 'pending'
        };
    }

    public function testConnection()
    {
        try {
            Log::info('Testing Shopify connection:', [
                'shop_domain' => $this->shopDomain,
                'has_access_token' => !empty($this->accessToken),
                'access_token_start' => substr($this->accessToken ?? '', 0, 10)
            ]);
            
            $response = $this->makeRequest('shop.json');
            $success = $response->getStatusCode() === 200;
            
            Log::info('Shopify connection test result:', [
                'success' => $success,
                'status_code' => $response->getStatusCode()
            ]);
            
            return $success;
            
        } catch (RequestException $e) {
            Log::error('Shopify Connection Test Failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function testOrdersPermission()
    {
        try {
            $response = $this->makeRequest('orders.json', ['limit' => 1]);
            Log::info('Orders permission test:', [
                'status' => $response->getStatusCode(),
                'response' => substr($response->getBody(), 0, 500)
            ]);
            return $response->getStatusCode() === 200;
            
        } catch (RequestException $e) {
            Log::error('Orders Permission Test Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getStoreInfo()
    {
        try {
            $response = $this->makeRequest('shop.json');
            $data = json_decode($response->getBody(), true);
            $shop = $data['shop'] ?? [];
            
            // Registrar info de tienda para depuración
            Log::info('Shopify Store Info Retrieved:', [
                'name' => $shop['name'] ?? 'N/A',
                'currency' => $shop['currency'] ?? 'N/A',
                'domain' => $shop['domain'] ?? 'N/A',
                'plan' => $shop['plan_name'] ?? 'N/A'
            ]);
            
            return [
                'store_name' => $shop['name'] ?? 'Shopify Store',
                'domain' => $shop['domain'] ?? $this->shopDomain,
                'email' => $shop['email'] ?? 'N/A',
                'currency' => $shop['currency'] ?? null, // Sin respaldo, dejar null si no está configurado
                'plan' => $shop['plan_name'] ?? 'Unknown',
                'country' => $shop['country_name'] ?? 'N/A',
                'timezone' => $shop['iana_timezone'] ?? 'N/A',
            ];

        } catch (RequestException $e) {
            Log::error('Shopify Store Info Error: ' . $e->getMessage());
            return [
                'store_name' => 'Shopify Store',
                'domain' => $this->shopDomain,
                'email' => 'N/A',
                'currency' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getProductsStats()
    {
        try {
            $products = $this->getProducts(250); // Obtener más productos para mejores estadísticas
            Log::info('Products fetched for stats', ['count' => $products->count()]);
            
            $lowStockProducts = $products->filter(function($product) {
                return $product['stock_quantity'] < 10 && $product['stock_quantity'] > 0;
            });

            $stats = [
                'total_products' => $products->count(),
                'in_stock' => $products->where('stock_status', 'instock')->count(),
                'out_of_stock' => $products->where('stock_status', 'outofstock')->count(),
                'total_inventory' => $products->sum('stock_quantity'),
                'avg_price' => $products->avg('price'),
                'min_price' => $products->min('price'),
                'max_price' => $products->max('price'),
                'low_stock_count' => $lowStockProducts->count(),
                'low_stock_products' => $lowStockProducts->map(function($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'stock_quantity' => $product['stock_quantity'],
                        'category' => $product['categories'],
                        'image' => $product['image']
                    ];
                })->values()->toArray(),
                'categories' => $products->groupBy('categories')->map(function($categoryProducts) {
                    return $categoryProducts->sum('stock_quantity');
                })->sortDesc()->take(10),
                'vendors' => $products->groupBy('vendor')->map->count()->sortDesc()->take(10),
                'price_ranges' => [
                    '0-10' => $products->filter(fn($p) => $p['price'] >= 0 && $p['price'] <= 10)->count(),
                    '11-50' => $products->filter(fn($p) => $p['price'] > 10 && $p['price'] <= 50)->count(),
                    '51-100' => $products->filter(fn($p) => $p['price'] > 50 && $p['price'] <= 100)->count(),
                    '101-500' => $products->filter(fn($p) => $p['price'] > 100 && $p['price'] <= 500)->count(),
                    '500+' => $products->filter(fn($p) => $p['price'] > 500)->count(),
                ]
            ];
            
            Log::info('Products stats calculated', [
                'total_products' => $stats['total_products'],
                'in_stock' => $stats['in_stock'],
                'categories_count' => $stats['categories']->count(),
                'categories' => $stats['categories']->toArray(),
                'all_products_data' => $products->map(function($p) {
                    return [
                        'title' => $p['name'] ?? 'N/A',
                        'final_category' => $p['categories'] ?? 'N/A',
                        'stock_quantity' => $p['stock_quantity'] ?? 0
                    ];
                })->toArray(),
                'categories_by_inventory' => $products->groupBy('categories')->map(function($categoryProducts) {
                    return [
                        'product_count' => $categoryProducts->count(),
                        'total_inventory' => $categoryProducts->sum('stock_quantity')
                    ];
                })->toArray()
            ]);
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Products Stats Error: ' . $e->getMessage());
            return [
                'total_products' => 0,
                'in_stock' => 0,
                'out_of_stock' => 0,
                'total_inventory' => 0,
                'avg_price' => 0,
                'categories' => collect(),
                'vendors' => collect(),
                'price_ranges' => []
            ];
        }
    }

    public function getOrdersStats($days = 30)
    {
        try {
            $orders = $this->getOrders(250, $days); // Obtener más órdenes para mejores estadísticas
            Log::info('Orders fetched for stats', ['count' => $orders->count(), 'days' => $days]);
            
            $stats = [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total'),
                'avg_order_value' => $orders->avg('total'),
                'total_items' => $orders->sum('items_count'),
                'orders_by_status' => $orders->groupBy('status')->map->count(),
                'orders_by_payment' => $orders->groupBy('payment_method')->map->count(),
                'daily_orders' => $orders->groupBy(function($order) {
                    return \Carbon\Carbon::parse($order['date_created'])->format('Y-m-d');
                })->map->count()->sortKeys(),
                'daily_revenue' => $orders->groupBy(function($order) {
                    return \Carbon\Carbon::parse($order['date_created'])->format('Y-m-d');
                })->map(function($dayOrders) {
                    return $dayOrders->sum('total');
                })->sortKeys(),
                'top_customers' => $orders->groupBy('customer_email')->map(function($customerOrders) {
                    return [
                        'name' => $customerOrders->first()['customer_name'],
                        'orders_count' => $customerOrders->count(),
                        'total_spent' => $customerOrders->sum('total')
                    ];
                })->sortByDesc('total_spent')->take(5)
            ];
            
            Log::info('Orders stats calculated', [
                'total_orders' => $stats['total_orders'],
                'total_revenue' => $stats['total_revenue'],
                'orders_by_status' => $stats['orders_by_status']->toArray(),
                'daily_revenue_days' => $stats['daily_revenue']->count()
            ]);
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Orders Stats Error: ' . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_revenue' => 0,
                'avg_order_value' => 0,
                'orders_by_status' => collect(),
                'daily_orders' => collect(),
                'daily_revenue' => collect()
            ];
        }
    }

    public function getMostSoldProducts($limit = 10, $days = 30)
    {
        try {
            $orders = $this->getOrders(250, $days);
            Log::info('Orders fetched for product stats', ['count' => $orders->count(), 'days' => $days, 'limit' => $limit]);
            
            $productSales = [];
            
            foreach ($orders as $order) {
                foreach ($order['line_items'] as $item) {
                    $key = $item['sku'] ?: $item['name'];
                    
                    if (!isset($productSales[$key])) {
                        $productSales[$key] = [
                            'product_name' => $item['name'],
                            'sku' => $item['sku'],
                            'total_quantity_sold' => 0,
                            'total_revenue' => 0,
                            'order_count' => 0
                        ];
                    }
                    
                    $productSales[$key]['total_quantity_sold'] += (int) $item['quantity'];
                    $productSales[$key]['total_revenue'] += (float) $item['total'] * (int) $item['quantity'];
                    $productSales[$key]['order_count']++;
                }
            }
            
            uasort($productSales, function($a, $b) {
                return $b['total_quantity_sold'] <=> $a['total_quantity_sold'];
            });
            
            $result = array_slice($productSales, 0, $limit, true);
            
            Log::info('Most sold products calculated', [
                'total_unique_products' => count($productSales),
                'returned_products' => count($result),
                'top_product' => !empty($result) ? array_values($result)[0]['product_name'] : 'None'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Most Sold Products Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getHighestRevenueProducts($limit = 10, $days = 30)
    {
        try {
            $orders = $this->getOrders(250, $days);
            Log::info('Orders fetched for highest revenue products', ['count' => $orders->count(), 'days' => $days, 'limit' => $limit]);
            
            $productRevenue = [];
            
            foreach ($orders as $order) {
                foreach ($order['line_items'] as $item) {
                    $key = $item['sku'] ?: $item['name'];
                    
                    if (!isset($productRevenue[$key])) {
                        $productRevenue[$key] = [
                            'product_name' => $item['name'],
                            'sku' => $item['sku'],
                            'total_quantity_sold' => 0,
                            'total_revenue' => 0,
                            'order_count' => 0,
                            'avg_price' => 0
                        ];
                    }
                    
                    $itemRevenue = (float) $item['total'] * (int) $item['quantity'];
                    $productRevenue[$key]['total_quantity_sold'] += (int) $item['quantity'];
                    $productRevenue[$key]['total_revenue'] += $itemRevenue;
                    $productRevenue[$key]['order_count']++;
                    $productRevenue[$key]['avg_price'] = $productRevenue[$key]['total_revenue'] / $productRevenue[$key]['total_quantity_sold'];
                }
            }
            
            uasort($productRevenue, function($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });
            
            $result = array_slice($productRevenue, 0, $limit, true);
            
            Log::info('Highest revenue products calculated', [
                'total_unique_products' => count($productRevenue),
                'returned_products' => count($result),
                'top_product' => !empty($result) ? array_values($result)[0]['product_name'] : 'None'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Highest Revenue Products Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getProductsWithFilters($limit = 50, $filters = [])
    {
        // Primero obtener todos los productos usando método existente
        $allProducts = $this->getProducts(250);
        
        // Aplicar filtros
        $filteredProducts = $allProducts->filter(function ($product) use ($filters) {
            // Filtro por categoría
            if (!empty($filters['category'])) {
                if (strtolower($product['categories'] ?? '') !== strtolower($filters['category'])) {
                    return false;
                }
            }
            
            // Filtro por nivel de stock
            if (!empty($filters['stock_level'])) {
                $stock = intval($product['stock_quantity'] ?? 0);
                
                switch ($filters['stock_level']) {
                    case 'out_of_stock':
                        if ($stock > 0) return false;
                        break;
                    case 'low_stock':
                        if ($stock >= 10 || $stock <= 0) return false;
                        break;
                    case 'in_stock':
                        if ($stock <= 0) return false;
                        break;
                }
            }
            
            // Filtro de búsqueda (nombre o SKU)
            if (!empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                $productName = strtolower($product['name'] ?? '');
                $productSku = strtolower($product['sku'] ?? '');
                
                if (strpos($productName, $searchTerm) === false && 
                    strpos($productSku, $searchTerm) === false) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Aplicar límite y retornar
        return $filteredProducts->take($limit);
    }

    public function getExportStats()
    {
        try {
            // Esto será manejado por el modelo ExportLog
            $stats = [
                'total_exports' => \App\Models\ExportLog::count(),
                'successful_exports' => \App\Models\ExportLog::where('success', true)->count(),
                'failed_exports' => \App\Models\ExportLog::where('success', false)->count(),
                'exports_by_format' => \App\Models\ExportLog::selectRaw('format, count(*) as count')
                    ->groupBy('format')
                    ->get()
                    ->pluck('count', 'format'),
                'exports_by_type' => \App\Models\ExportLog::selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type'),
                'recent_exports' => \App\Models\ExportLog::where('created_at', '>=', now()->subDays(7))
                    ->selectRaw('DATE(created_at) as date, count(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('count', 'date')
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Export Stats Error: ' . $e->getMessage());
            return [
                'total_exports' => 0,
                'successful_exports' => 0,
                'failed_exports' => 0,
                'exports_by_format' => collect(),
                'exports_by_type' => collect(),
                'recent_exports' => collect()
            ];
        }
    }

    public static function buildAuthUrl($shop, $apiKey, $scopes, $redirectUri)
    {
        $params = http_build_query([
            'client_id' => $apiKey,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'state' => csrf_token(),
        ]);

        return "https://{$shop}.myshopify.com/admin/oauth/authorize?{$params}";
    }

    public function exchangeCodeForToken($shop, $code)
    {
        try {
            $response = $this->client->post("https://{$shop}.myshopify.com/admin/oauth/access_token", [
                'json' => [
                    'client_id' => config('services.shopify.api_key'),
                    'client_secret' => config('services.shopify.api_secret'),
                    'code' => $code,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;

        } catch (RequestException $e) {
            Log::error('Shopify Token Exchange Error: ' . $e->getMessage());
            throw new \Exception('Error obteniendo token de Shopify: ' . $e->getMessage());
        }
    }
}