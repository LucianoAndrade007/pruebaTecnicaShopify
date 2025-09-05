@extends('layouts.app')

@section('title', 'Visor de Logs - Amplifica')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-file-alt"></i> Visor de Logs del Sistema
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('logs.download') }}" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Descargar Logs
            </a>
            <form method="POST" action="{{ route('logs.clear') }}" style="display: inline;" 
                  onsubmit="return confirm('¿Estás seguro de que quieres limpiar todos los logs? Esta acción no se puede deshacer.')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i> Limpiar Logs
                </button>
            </form>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Estadísticas -->
@if(isset($stats))
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary">{{ number_format($stats['total'] ?? 0) }}</h4>
                <p class="mb-0">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-danger">{{ number_format($stats['errors'] ?? 0) }}</h4>
                <p class="mb-0">Errores</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-warning">{{ number_format($stats['warnings'] ?? 0) }}</h4>
                <p class="mb-0">Advertencias</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info">{{ number_format($stats['info'] ?? 0) }}</h4>
                <p class="mb-0">Info</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-secondary">{{ number_format($stats['debug'] ?? 0) }}</h4>
                <p class="mb-0">Debug</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success">{{ number_format($totalLogs ?? 0) }}</h4>
                <p class="mb-0">Total en Archivo</p>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('logs.index') }}" class="row g-3">
            <div class="col-md-2">
                <label for="level" class="form-label">Nivel</label>
                <select name="level" id="level" class="form-select">
                    <option value="">Todos los niveles</option>
                    <option value="error" {{ $level == 'error' ? 'selected' : '' }}>Error</option>
                    <option value="warning" {{ $level == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="info" {{ $level == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="debug" {{ $level == 'debug' ? 'selected' : '' }}>Debug</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Buscar en mensaje o contexto" value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Fecha desde</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">Fecha hasta</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <label for="per_page" class="form-label">Por página</label>
                <select name="per_page" id="per_page" class="form-select">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Alertas -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(isset($message))
    <div class="alert alert-info" role="alert">
        {{ $message }}
    </div>
@endif

<!-- Logs -->
@if(isset($paginatedLogs) && $paginatedLogs->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list"></i> Logs del Sistema 
                @if(isset($pagination))
                    ({{ number_format($pagination['total']) }} registros)
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Fecha/Hora</th>
                            <th style="width: 80px;">Nivel</th>
                            <th>Mensaje</th>
                            <th style="width: 60px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginatedLogs as $index => $log)
                        <tr class="
                            @if($log['level'] == 'ERROR') table-danger
                            @elseif($log['level'] == 'WARNING') table-warning  
                            @elseif($log['level'] == 'INFO') table-info
                            @else table-light
                            @endif
                        ">
                            <td>
                                <small>{{ \Carbon\Carbon::parse($log['datetime'])->format('d/m/Y H:i:s') }}</small>
                            </td>
                            <td>
                                @if($log['level'] == 'ERROR')
                                    <span class="badge bg-danger">{{ $log['level'] }}</span>
                                @elseif($log['level'] == 'WARNING')
                                    <span class="badge bg-warning text-dark">{{ $log['level'] }}</span>
                                @elseif($log['level'] == 'INFO')
                                    <span class="badge bg-info">{{ $log['level'] }}</span>
                                @elseif($log['level'] == 'DEBUG')
                                    <span class="badge bg-secondary">{{ $log['level'] }}</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $log['level'] }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="log-message">
                                    {{ Str::limit($log['message'], 100) }}
                                    @if(strlen($log['message']) > 100 || !empty(trim($log['context'])))
                                        <button class="btn btn-link btn-sm p-0 ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#logDetail{{ $index }}" aria-expanded="false">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    @endif
                                </div>
                                @if(strlen($log['message']) > 100 || !empty(trim($log['context'])))
                                    <div class="collapse mt-2" id="logDetail{{ $index }}">
                                        <div class="card card-body bg-light">
                                            <strong>Mensaje completo:</strong>
                                            <pre class="mb-2"><small>{{ $log['message'] }}</small></pre>
                                            @if(!empty(trim($log['context'])))
                                                <strong>Contexto:</strong>
                                                <pre class="mb-0"><small>{{ $log['context'] }}</small></pre>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#logModal{{ $index }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal para ver log completo -->
                        <div class="modal fade" id="logModal{{ $index }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detalle del Log - {{ $log['level'] }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Fecha/Hora:</strong> {{ $log['datetime'] }}</p>
                                        <p><strong>Nivel:</strong> {{ $log['level'] }}</p>
                                        <p><strong>Entorno:</strong> {{ $log['env'] ?? 'N/A' }}</p>
                                        <hr>
                                        <strong>Log Completo:</strong>
                                        <pre class="bg-light p-3 border rounded"><small>{{ $log['full_line'] }}</small></pre>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        @if(isset($pagination) && $pagination['last_page'] > 1)
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        Mostrando {{ number_format($pagination['from']) }} a {{ number_format($pagination['to']) }} 
                        de {{ number_format($pagination['total']) }} registros
                    </small>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Paginación de logs">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <!-- Anterior -->
                            @if($pagination['current_page'] > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">Anterior</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            @endif

                            <!-- Páginas -->
                            @php
                                $start = max(1, $pagination['current_page'] - 2);
                                $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                            @endphp

                            @if($start > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">1</a>
                                </li>
                                @if($start > 2)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endif

                            @for($i = $start; $i <= $end; $i++)
                                @if($i == $pagination['current_page'])
                                    <li class="page-item active">
                                        <span class="page-link">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            @if($end < $pagination['last_page'])
                                @if($end < $pagination['last_page'] - 1)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['last_page']]) }}">{{ $pagination['last_page'] }}</a>
                                </li>
                            @endif

                            <!-- Siguiente -->
                            @if($pagination['current_page'] < $pagination['last_page'])
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">Siguiente</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Siguiente</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        @endif
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
            <h4>No se encontraron logs</h4>
            <p class="text-muted">
                No hay logs que coincidan con los filtros aplicados o el archivo de logs está vacío.
            </p>
            <a href="{{ route('logs.index') }}" class="btn btn-primary">
                <i class="fas fa-refresh"></i> Actualizar
            </a>
        </div>
    </div>
@endif

<style>
.log-message {
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: 'Courier New', monospace;
    font-size: 0.85em;
    line-height: 1.4;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.7em;
}

.modal-body pre {
    max-height: 400px;
    overflow-y: auto;
}
</style>
@endsection