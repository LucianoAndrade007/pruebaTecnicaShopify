<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use App\Models\ExportLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController extends Controller
{
    private function getShopifyService()
    {
        // Override config with session data if connected via OAuth
        if (session('shopify_connected')) {
            config([
                'services.shopify.shop_domain' => session('shopify_shop_domain'),
                'services.shopify.access_token' => session('shopify_access_token')
            ]);
        }
        
        return new ShopifyService();
    }

    public function exportProducts(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        $startTime = microtime(true);
        $format = $request->get('format', 'csv');
        
        try {
            $shopify = $this->getShopifyService();
            $products = $shopify->getProducts(100);
            
            $response = match($format) {
                'excel' => $this->exportProductsToExcel($products),
                'pdf' => $this->exportProductsToPDF($products),
                default => $this->exportProductsToCSV($products)
            };
            
            // Registrar exportación exitosa
            $this->logExport([
                'type' => 'products',
                'format' => $format,
                'records_count' => $products->count(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
                'export_params' => [
                    'limit' => 100,
                    'format' => $format
                ],
                'success' => true
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            // Registrar exportación fallida
            $this->logExport([
                'type' => 'products',
                'format' => $format,
                'records_count' => 0,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
                'success' => false,
                'error_message' => $e->getMessage()
            ]);
            
            Log::error('Export Products Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al exportar productos: ' . $e->getMessage()]);
        }
    }

    public function exportOrders(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        $startTime = microtime(true);
        $format = $request->get('format', 'csv');
        $days = $request->get('days', 30);

        try {
            $shopify = $this->getShopifyService();
            $orders = $shopify->getOrders(100, $days);

            $response = match($format) {
                'excel' => $this->exportOrdersToExcel($orders),
                'pdf' => $this->exportOrdersToPDF($orders),
                default => $this->exportOrdersToCSV($orders)
            };
            
            // Registrar exportación exitosa
            $this->logExport([
                'type' => 'orders',
                'format' => $format,
                'records_count' => $orders->count(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
                'export_params' => [
                    'limit' => 100,
                    'days' => $days,
                    'format' => $format
                ],
                'success' => true
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            // Registrar exportación fallida
            $this->logExport([
                'type' => 'orders',
                'format' => $format,
                'records_count' => 0,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000),
                'export_params' => [
                    'days' => $days
                ],
                'success' => false,
                'error_message' => $e->getMessage()
            ]);
            
            Log::error('Export Orders Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al exportar pedidos: ' . $e->getMessage()]);
        }
    }

    private function exportProductsToCSV($products)
    {
        $filename = 'productos_shopify_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'ID', 'Nombre', 'SKU', 'Precio', 'Precio Regular', 'Precio Oferta',
                'Stock', 'Estado Stock', 'Categorías', 'Vendor'
            ]);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product['id'],
                    $product['name'],
                    $product['sku'],
                    $product['price'],
                    $product['regular_price'],
                    $product['sale_price'] ?? '',
                    $product['stock_quantity'],
                    $product['stock_status'],
                    $product['categories'],
                    $product['vendor'] ?? ''
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function exportOrdersToCSV($orders)
    {
        $filename = 'pedidos_shopify_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'ID', 'Número', 'Estado', 'Total', 'Moneda', 'Cliente',
                'Email', 'Fecha', 'Método Pago', 'Cantidad Items'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order['id'],
                    $order['order_number'],
                    $order['status'],
                    $order['total'],
                    $order['currency'],
                    $order['customer_name'],
                    $order['customer_email'],
                    $order['date_created'],
                    $order['payment_method'],
                    $order['items_count']
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function exportProductsToExcel($products)
    {
        $filename = 'productos_shopify_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $headers = ['ID', 'Nombre', 'SKU', 'Precio', 'Precio Regular', 'Precio Oferta', 
                   'Stock', 'Estado Stock', 'Categorías', 'Vendor'];
        $sheet->fromArray([$headers], null, 'A1');
        
        // Datos
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValueExplicit('A' . $row, $product['id'], DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValueExplicit('C' . $row, $product['sku'], DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, (float) $product['price']);
            $sheet->setCellValue('E' . $row, (float) $product['regular_price']);
            $sheet->setCellValue('F' . $row, $product['sale_price'] ? (float) $product['sale_price'] : '');
            $sheet->setCellValue('G' . $row, (int) $product['stock_quantity']);
            $sheet->setCellValue('H' . $row, $product['stock_status']);
            $sheet->setCellValue('I' . $row, $product['categories']);
            $sheet->setCellValue('J' . $row, $product['vendor'] ?? '');
            $row++;
        }
        
        // Estilo para headers
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        
        // Formato para columnas específicas
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // ID como texto
        $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // SKU como texto
        $sheet->getStyle('D:F')->getNumberFormat()->setFormatCode('$#,##0.00'); // Precios con formato moneda
        $sheet->getStyle('G:G')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Stock como número entero
        
        // Crear archivo temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'productos_shopify') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportOrdersToExcel($orders)
    {
        $filename = 'pedidos_shopify_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $headers = ['ID', 'Número', 'Estado', 'Total', 'Moneda', 'Cliente', 
                   'Email', 'Fecha', 'Método Pago', 'Cantidad Items'];
        $sheet->fromArray([$headers], null, 'A1');
        
        // Datos
        $row = 2;
        foreach ($orders as $order) {
            $sheet->setCellValueExplicit('A' . $row, $order['id'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $order['order_number'], DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, ucfirst($order['status']));
            $sheet->setCellValue('D' . $row, (float) $order['total']);
            $sheet->setCellValue('E' . $row, $order['currency']);
            $sheet->setCellValue('F' . $row, $order['customer_name']);
            $sheet->setCellValueExplicit('G' . $row, $order['customer_email'], DataType::TYPE_STRING);
            $sheet->setCellValue('H' . $row, \Carbon\Carbon::parse($order['date_created'])->format('d/m/Y H:i'));
            $sheet->setCellValue('I' . $row, $order['payment_method']);
            $sheet->setCellValue('J' . $row, (int) $order['items_count']);
            $row++;
        }
        
        // Estilo para headers
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        
        // Formato para columnas específicas
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // ID como texto
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // Order number como texto
        $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode('$#,##0.00'); // Total con formato moneda
        $sheet->getStyle('G:G')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // Email como texto
        $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Items count como número
        
        // Crear archivo temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'pedidos_shopify') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportProductsToPDF($products)
    {
        $html = $this->generateProductsPDFHTML($products);
        $filename = 'productos_shopify_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Configurar opciones de DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportOrdersToPDF($orders)
    {
        $html = $this->generateOrdersPDFHTML($orders);
        $filename = 'pedidos_shopify_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Configurar opciones de DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function generateProductsPDFHTML($products)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Productos Shopify</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .text-center { text-align: center; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <h1>Productos Shopify</h1>
            <p><strong>Fecha de exportación:</strong> ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Total de productos:</strong> ' . $products->count() . '</p>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>SKU</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Categoría</th>
                        <th>Vendor</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($products as $product) {
            $html .= '<tr>
                <td>' . $product['id'] . '</td>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td>' . htmlspecialchars($product['sku']) . '</td>
                <td>$' . number_format($product['price'], 2) . '</td>
                <td class="text-center">' . $product['stock_quantity'] . '</td>
                <td>' . ($product['stock_status'] == 'instock' ? 'En Stock' : 'Sin Stock') . '</td>
                <td>' . htmlspecialchars($product['categories']) . '</td>
                <td>' . htmlspecialchars($product['vendor'] ?? 'N/A') . '</td>
            </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Generado por PruebaTécnicaAmplifica - ' . config('app.url') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    private function generateOrdersPDFHTML($orders)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Órdenes Shopify</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .text-center { text-align: center; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <h1>Pedidos Shopify</h1>
            <p><strong>Fecha de exportación:</strong> ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Total de pedidos:</strong> ' . $orders->count() . '</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Fecha</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($orders as $order) {
            $html .= '<tr>
                <td>' . htmlspecialchars($order['order_number']) . '</td>
                <td>' . ucfirst($order['status']) . '</td>
                <td>$' . number_format($order['total'], 2) . ' ' . $order['currency'] . '</td>
                <td>' . htmlspecialchars($order['customer_name']) . '</td>
                <td>' . htmlspecialchars($order['customer_email']) . '</td>
                <td>' . (\Carbon\Carbon::parse($order['date_created'])->format('d/m/Y')) . '</td>
                <td class="text-center">' . $order['items_count'] . '</td>
            </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Generado por PruebaTécnicaAmplifica - ' . config('app.url') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    private function logExport($data)
    {
        try {
            $filename = '';
            if (isset($data['type']) && isset($data['format'])) {
                $timestamp = date('Y-m-d_H-i-s');
                $filename = $data['type'] . '_shopify_' . $timestamp . '.' . 
                           ($data['format'] === 'excel' ? 'xlsx' : $data['format']);
            }

            ExportLog::create([
                'type' => $data['type'] ?? 'unknown',
                'format' => $data['format'] ?? 'unknown',
                'records_count' => $data['records_count'] ?? 0,
                'filename' => $filename,
                'user_session' => session()->getId(),
                'execution_time_ms' => $data['execution_time_ms'] ?? null,
                'export_params' => $data['export_params'] ?? [],
                'success' => $data['success'] ?? false,
                'error_message' => $data['error_message'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error logging export: ' . $e->getMessage());
        }
    }

    public function exportHistory(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        $query = ExportLog::query()->orderBy('created_at', 'desc');

        // Filtros opcionales
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('format') && $request->format !== 'all') {
            $query->where('format', $request->format);
        }

        if ($request->has('status')) {
            if ($request->status === 'success') {
                $query->where('success', true);
            } elseif ($request->status === 'failed') {
                $query->where('success', false);
            }
        }

        $logs = $query->paginate(20);

        // Estadísticas
        $stats = [
            'total_exports' => ExportLog::count(),
            'successful_exports' => ExportLog::where('success', true)->count(),
            'failed_exports' => ExportLog::where('success', false)->count(),
            'total_records_exported' => ExportLog::where('success', true)->sum('records_count'),
            'avg_execution_time' => ExportLog::where('success', true)->avg('execution_time_ms'),
            'most_used_format' => ExportLog::selectRaw('format, count(*) as count')
                                          ->where('success', true)
                                          ->groupBy('format')
                                          ->orderBy('count', 'desc')
                                          ->first()?->format ?? 'N/A',
        ];

        return view('exports.history', compact('logs', 'stats'));
    }
}