@extends('layouts.app')

@section('title', 'Historial de Exportaciones - Amplifica')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Historial de Exportaciones</h2>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-primary text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ number_format($stats['total_exports']) }}</h3>
                            <p class="card-text mb-0">Total Exportaciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ number_format($stats['successful_exports']) }}</h3>
                            <p class="card-text mb-0">Exitosas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-danger text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ number_format($stats['failed_exports']) }}</h3>
                            <p class="card-text mb-0">Fallidas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-info text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ number_format($stats['total_records_exported']) }}</h3>
                            <p class="card-text mb-0">Registros Exportados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ $stats['avg_execution_time'] ? number_format($stats['avg_execution_time']) . 'ms' : 'N/A' }}</h3>
                            <p class="card-text mb-0">Tiempo Promedio</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card text-center bg-secondary text-white">
                        <div class="card-body">
                            <h3 class="card-title">{{ strtoupper($stats['most_used_format']) }}</h3>
                            <p class="card-text mb-0">Formato Más Usado</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('export.history') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Tipo</label>
                            <select name="type" id="type" class="form-select">
                                <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Todos</option>
                                <option value="products" {{ request('type') === 'products' ? 'selected' : '' }}>Productos</option>
                                <option value="orders" {{ request('type') === 'orders' ? 'selected' : '' }}>Órdenes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="format" class="form-label">Formato</label>
                            <select name="format" id="format" class="form-select">
                                <option value="all" {{ request('format') === 'all' ? 'selected' : '' }}>Todos</option>
                                <option value="excel" {{ request('format') === 'excel' ? 'selected' : '' }}>Excel</option>
                                <option value="pdf" {{ request('format') === 'pdf' ? 'selected' : '' }}>PDF</option>
                                <option value="csv" {{ request('format') === 'csv' ? 'selected' : '' }}>CSV</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select name="status" id="status" class="form-select">
                                <option value="" {{ !request('status') ? 'selected' : '' }}>Todos</option>
                                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Exitosos</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallidos</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('export.history') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Registros de Exportación</h5>
                </div>
                <div class="card-body">
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Formato</th>
                                        <th>Registros</th>
                                        <th>Tiempo</th>
                                        <th>Estado</th>
                                        <th>Archivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                    <tr class="{{ $log->success ? '' : 'table-danger' }}">
                                        <td>
                                            <small>{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                                        </td>
                                        <td>
                                            {!! $log->type_display !!}
                                        </td>
                                        <td>
                                            {!! $log->format_display !!}
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ number_format($log->records_count) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $log->formatted_execution_time }}</small>
                                        </td>
                                        <td>
                                            {!! $log->status_display !!}
                                        </td>
                                        <td>
                                            @if($log->success && $log->filename)
                                                <code class="text-muted">{{ $log->filename }}</code>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if(!$log->success && $log->error_message)
                                    <tr>
                                        <td colspan="7" class="bg-light">
                                            <small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Error:</strong> {{ $log->error_message }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    Mostrando {{ $logs->firstItem() }} - {{ $logs->lastItem() }} de {{ $logs->total() }} registros
                                </small>
                            </div>
                            <div>
                                {{ $logs->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-export fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay exportaciones registradas</h4>
                            <p class="text-muted">Realiza tu primera exportación desde la sección de productos u órdenes.</p>
                            <div class="mt-3">
                                <a href="{{ route('products.index') }}" class="btn btn-primary me-2">
                                    <i class="fas fa-box"></i> Ver Productos
                                </a>
                                <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-shopping-cart"></i> Ver Órdenes
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection