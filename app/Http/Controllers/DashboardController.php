<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private $shopify;

    public function __construct()
    {
        // Servicio Shopify se inicializa dinámicamente
    }

    private function getShopifyService()
    {
        // Sobrescribir configuración con datos de sesión si está conectado via OAuth
        if (session('shopify_connected')) {
            config([
                'services.shopify.shop_domain' => session('shopify_shop_domain'),
                'services.shopify.access_token' => session('shopify_access_token')
            ]);
        }
        
        return new ShopifyService();
    }

    public function index()
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $shopify = $this->getShopifyService();
            
            // Probar conexión actual a la API de Shopify
            $connectionStatus = $shopify->testConnection();
            
            if ($connectionStatus) {
                // Conexión API exitosa - estamos conectados
                $isConnected = true;
                $storeInfo = $shopify->getStoreInfo();
                
                Log::info('Shopify connection successful, fetching data...');
                
                // Obtener estadísticas completas
                $productStats = $shopify->getProductsStats();
                Log::info('Product stats:', ['total_products' => $productStats['total_products'] ?? 0]);
                
                $orderStats = $shopify->getOrdersStats(30);
                Log::info('Order stats:', ['total_orders' => $orderStats['total_orders'] ?? 0]);
                
                $mostSoldProducts = $shopify->getMostSoldProducts(10, 30);
                Log::info('Most sold products:', ['count' => count($mostSoldProducts)]);
                
                $highestRevenueProducts = $shopify->getHighestRevenueProducts(10, 30);
                Log::info('Highest revenue products:', ['count' => count($highestRevenueProducts)]);
                
                $exportStats = $shopify->getExportStats();
                
            } else {
                // API connection failed
                Log::warning('Shopify connection test failed');
                $isConnected = false;
                $storeInfo = ['error' => 'Unable to connect to Shopify API'];
                $productStats = ['total_products' => 0];
                $orderStats = ['total_orders' => 0, 'total_revenue' => 0];
                $mostSoldProducts = [];
                $highestRevenueProducts = [];
                $exportStats = ['total_exports' => 0];
            }
            
            return view('dashboard', compact(
                'connectionStatus', 
                'storeInfo', 
                'isConnected', 
                'productStats', 
                'orderStats', 
                'mostSoldProducts',
                'highestRevenueProducts',
                'exportStats'
            ));
            
        } catch (\Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage());
            return view('dashboard', [
                'connectionStatus' => false,
                'storeInfo' => ['error' => $e->getMessage()],
                'isConnected' => false,
                'productStats' => ['total_products' => 0],
                'orderStats' => ['total_orders' => 0, 'total_revenue' => 0],
                'mostSoldProducts' => [],
                'exportStats' => ['total_exports' => 0]
            ]);
        }
    }

    public function products(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $shopify = $this->getShopifyService();
            
            // Get filter parameters
            $limit = $request->get('limit', 50);
            $category = $request->get('category');
            $stockLevel = $request->get('stock_level');
            $search = $request->get('search');
            
            // Build filters array
            $filters = [
                'category' => $category,
                'stock_level' => $stockLevel,
                'search' => $search
            ];
            
            // Get products with filters
            $products = $shopify->getProductsWithFilters($limit, $filters);
            
            // Get available categories for the filter dropdown
            $allProducts = $shopify->getProducts(250); // Get more products to extract categories
            $categories = $allProducts->pluck('categories')->unique()->filter()->sort()->values();
            
            Log::info('Products retrieved with filters', [
                'count' => $products->count(),
                'filters' => array_filter($filters),
                'categories_found' => $categories->count()
            ]);
            
            return view('products.index', compact('products', 'categories', 'filters'));
            
        } catch (\Exception $e) {
            Log::error('Products Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cargar productos: ' . $e->getMessage()]);
        }
    }

    public function orders(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $shopify = $this->getShopifyService();
            
            // Get filter parameters
            $limit = $request->get('limit', 50);
            $days = $request->get('days', 30);
            $periodType = $request->get('period_type', 'preset');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $customerSearch = $request->get('customer_search');
            $status = $request->get('status');
            
            // Build filters array
            $filters = [
                'period_type' => $periodType,
                'days' => $days,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'customer_search' => $customerSearch,
                'status' => $status
            ];
            
            $orders = $shopify->getOrdersWithFilters($limit, $filters);
            
            // Debug: Log info sobre órdenes recibidas
            Log::info('Orders retrieved with filters', [
                'count' => $orders->count(),
                'filters' => array_filter($filters)
            ]);
            
            return view('orders.index', compact('orders', 'days', 'filters'));
            
        } catch (\Exception $e) {
            Log::error('Orders Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cargar órdenes: ' . $e->getMessage()]);
        }
    }

    public function getDashboardData()
    {
        if (!session('authenticated')) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $shopify = $this->getShopifyService();
            
            // Test actual connection to Shopify API
            $connectionStatus = $shopify->testConnection();
            
            if ($connectionStatus) {
                // API connection successful - we are connected
                $isConnected = true;
                $storeInfo = $shopify->getStoreInfo();
                
                Log::info('AJAX: Shopify connection successful, fetching data...');
                
                // Get comprehensive statistics
                $productStats = $shopify->getProductsStats();
                $orderStats = $shopify->getOrdersStats(30);
                $mostSoldProducts = $shopify->getMostSoldProducts(10, 30);
                $highestRevenueProducts = $shopify->getHighestRevenueProducts(10, 30);
                $exportStats = $shopify->getExportStats();
                
            } else {
                // API connection failed
                Log::warning('AJAX: Shopify connection test failed');
                $isConnected = false;
                $storeInfo = ['error' => 'Unable to connect to Shopify API'];
                $productStats = ['total_products' => 0];
                $orderStats = ['total_orders' => 0, 'total_revenue' => 0];
                $mostSoldProducts = [];
                $highestRevenueProducts = [];
                $exportStats = ['total_exports' => 0];
            }
            
            return response()->json([
                'success' => true,
                'connectionStatus' => $connectionStatus,
                'isConnected' => $isConnected,
                'storeInfo' => $storeInfo,
                'productStats' => $productStats,
                'orderStats' => $orderStats,
                'mostSoldProducts' => $mostSoldProducts,
                'highestRevenueProducts' => $highestRevenueProducts,
                'exportStats' => $exportStats,
                'lastUpdate' => now()->format('d/m/Y H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard AJAX Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'lastUpdate' => now()->format('d/m/Y H:i:s')
            ], 500);
        }
    }
}