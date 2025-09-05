<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $logPath = storage_path('logs/laravel.log');
            
            // Verificar si el archivo de log existe
            if (!File::exists($logPath)) {
                return view('logs.index', [
                    'logs' => collect(),
                    'totalLogs' => 0,
                    'stats' => [
                        'total' => 0,
                        'errors' => 0,
                        'warnings' => 0,
                        'info' => 0,
                        'debug' => 0
                    ],
                    'message' => 'No se encontró el archivo de logs.'
                ]);
            }

            // Obtener parámetros de filtro
            $level = $request->get('level');
            $search = $request->get('search');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);

            // Leer y procesar logs
            $logs = $this->parseLogFile($logPath);
            $totalLogs = $logs->count();

            // Calcular estadísticas
            $stats = $this->calculateStats($logs);

            // Aplicar filtros
            $filteredLogs = $this->applyFilters($logs, $level, $search, $dateFrom, $dateTo);

            // Paginación manual
            $offset = ($page - 1) * $perPage;
            $paginatedLogs = $filteredLogs->slice($offset, $perPage);
            $totalFiltered = $filteredLogs->count();

            // Información de paginación
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiltered,
                'last_page' => ceil($totalFiltered / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $totalFiltered)
            ];

            return view('logs.index', compact(
                'paginatedLogs',
                'totalLogs', 
                'stats',
                'pagination',
                'level',
                'search',
                'dateFrom',
                'dateTo',
                'perPage'
            ));

        } catch (\Exception $e) {
            return view('logs.index', [
                'logs' => collect(),
                'totalLogs' => 0,
                'stats' => [
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                    'info' => 0,
                    'debug' => 0
                ],
                'message' => 'Error al leer los logs: ' . $e->getMessage()
            ]);
        }
    }

    private function parseLogFile($logPath)
    {
        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $logs = collect();
        $currentLog = null;

        foreach ($lines as $line) {
            // Detectar nueva entrada de log con patrón [YYYY-MM-DD HH:MM:SS]
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/', $line, $matches)) {
                // Guardar log anterior si existe
                if ($currentLog) {
                    $logs->push($currentLog);
                }

                // Crear nuevo log
                $currentLog = [
                    'datetime' => $matches[1],
                    'env' => $matches[2],
                    'level' => strtoupper($matches[3]),
                    'message' => $matches[4],
                    'context' => '',
                    'full_line' => $line
                ];
            } elseif ($currentLog && !empty(trim($line))) {
                // Agregar líneas adicionales al contexto (stack traces, etc.)
                $currentLog['context'] .= "\n" . $line;
                $currentLog['full_line'] .= "\n" . $line;
            }
        }

        // Agregar último log
        if ($currentLog) {
            $logs->push($currentLog);
        }

        // Ordenar por fecha descendente (más recientes primero)
        return $logs->sortByDesc('datetime')->values();
    }

    private function calculateStats($logs)
    {
        return [
            'total' => $logs->count(),
            'errors' => $logs->where('level', 'ERROR')->count(),
            'warnings' => $logs->where('level', 'WARNING')->count(),
            'info' => $logs->where('level', 'INFO')->count(),
            'debug' => $logs->where('level', 'DEBUG')->count(),
        ];
    }

    private function applyFilters($logs, $level, $search, $dateFrom, $dateTo)
    {
        return $logs->filter(function ($log) use ($level, $search, $dateFrom, $dateTo) {
            // Filtro por nivel
            if ($level && $log['level'] !== strtoupper($level)) {
                return false;
            }

            // Filtro por búsqueda
            if ($search) {
                $searchTerm = strtolower($search);
                $message = strtolower($log['message']);
                $context = strtolower($log['context']);
                
                if (strpos($message, $searchTerm) === false && 
                    strpos($context, $searchTerm) === false) {
                    return false;
                }
            }

            // Filtro por fecha
            if ($dateFrom || $dateTo) {
                $logDate = Carbon::parse($log['datetime'])->format('Y-m-d');
                
                if ($dateFrom && $logDate < $dateFrom) {
                    return false;
                }
                
                if ($dateTo && $logDate > $dateTo) {
                    return false;
                }
            }

            return true;
        });
    }

    public function clear(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (File::exists($logPath)) {
                File::put($logPath, '');
                return redirect()->route('logs.index')->with('success', 'Logs limpiados exitosamente.');
            }
            
            return redirect()->route('logs.index')->with('error', 'No se encontró el archivo de logs.');
            
        } catch (\Exception $e) {
            return redirect()->route('logs.index')->with('error', 'Error al limpiar logs: ' . $e->getMessage());
        }
    }

    public function download(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (File::exists($logPath)) {
                return response()->download($logPath, 'laravel-logs-' . date('Y-m-d-H-i-s') . '.log');
            }
            
            return redirect()->route('logs.index')->with('error', 'No se encontró el archivo de logs.');
            
        } catch (\Exception $e) {
            return redirect()->route('logs.index')->with('error', 'Error al descargar logs: ' . $e->getMessage());
        }
    }
}