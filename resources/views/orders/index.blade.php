@extends('layouts.app')

@section('title', 'Pedidos - Amplifica')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-shopping-cart"></i> Pedidos Shopify
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('orders.export', ['format' => 'excel', 'days' => $days ?? 30]) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
            <a href="{{ route('orders.export', ['format' => 'csv', 'days' => $days ?? 30]) }}" class="btn btn-info btn-sm">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="{{ route('orders.export', ['format' => 'pdf', 'days' => $days ?? 30]) }}" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="period_type" class="form-label">Período</label>
                <select name="period_type" id="period_type" class="form-select" onchange="toggleDateInputs()">
                    <option value="preset" {{ request('period_type', 'preset') == 'preset' ? 'selected' : '' }}>Predefinido</option>
                    <option value="custom" {{ request('period_type') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                </select>
            </div>
            <div class="col-md-3" id="preset-period" style="{{ request('period_type') == 'custom' ? 'display: none;' : '' }}">
                <label for="days" class="form-label">Rango</label>
                <select name="days" id="days" class="form-select">
                    <option value="7" {{ ($days ?? 30) == 7 ? 'selected' : '' }}>Últimos 7 días</option>
                    <option value="30" {{ ($days ?? 30) == 30 ? 'selected' : '' }}>Últimos 30 días</option>
                    <option value="90" {{ ($days ?? 30) == 90 ? 'selected' : '' }}>Últimos 90 días</option>
                </select>
            </div>
            <div class="col-md-3" id="custom-dates" style="{{ request('period_type') != 'custom' ? 'display: none;' : '' }}">
                <div class="row">
                    <div class="col-6">
                        <label for="date_from" class="form-label">Desde</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-6">
                        <label for="date_to" class="form-label">Hasta</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <label for="customer_search" class="form-label">Cliente</label>
                <input type="text" name="customer_search" id="customer_search" class="form-control" placeholder="Nombre o email" value="{{ request('customer_search') }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Procesando</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completado</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Fallido</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="per_page" class="form-label">Resultados por página</label>
                <select name="per_page" id="per_page" class="form-select">
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if($orders && $orders->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list"></i> Lista de Órdenes ({{ $orders->count() }} órdenes - Últimos {{ $days ?? 30 }} días)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Items</th>
                            <th>Método Pago</th>
                            <th>Fecha</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td>
                                <code>{{ $order['id'] }}</code>
                            </td>
                            <td>
                                <strong>#{{ $order['order_number'] }}</strong>
                            </td>
                            <td>
                                <strong>{{ $order['customer_name'] }}</strong>
                            </td>
                            <td>
                                <small>{{ $order['customer_email'] }}</small>
                            </td>
                            <td>
                                <strong>${{ number_format($order['total'], 2) }}</strong>
                                <br><small class="text-muted">{{ $order['currency'] }}</small>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($order['status']) {
                                        'completed' => 'bg-success',
                                        'processing' => 'bg-primary',
                                        'pending' => 'bg-warning',
                                        'cancelled' => 'bg-danger',
                                        'refunded' => 'bg-secondary',
                                        default => 'bg-info'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($order['status']) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $order['items_count'] }}</span>
                            </td>
                            <td>
                                <small>{{ $order['payment_method'] }}</small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($order['date_created'])->format('d/m/Y H:i') }}
                                </small>
                            </td>
                            <td>
                                @if(count($order['line_items']) > 0)
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal{{ $order['id'] }}">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                @else
                                    <small class="text-muted">Sin items</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            <small>
                <i class="fas fa-info-circle"></i> 
                Los datos se obtienen en tiempo real desde la API de Shopify
            </small>
        </div>
    </div>

    <!-- Modals para ver productos de cada orden -->
    @foreach($orders as $order)
        @if(count($order['line_items']) > 0)
        <div class="modal fade" id="orderModal{{ $order['id'] }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Productos - Orden #{{ $order['order_number'] }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>SKU</th>
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order['line_items'] as $item)
                                    <tr>
                                        <td><strong>{{ $item['name'] }}</strong></td>
                                        <td><code>{{ $item['sku'] }}</code></td>
                                        <td><span class="badge bg-info">{{ $item['quantity'] }}</span></td>
                                        <td><strong>${{ number_format($item['total'], 2) }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach

@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>No se encontraron órdenes</h4>
            <p class="text-muted">
                No hay órdenes en el período seleccionado o hay un problema con la conexión a Shopify.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
@endif

<script>
function toggleDateInputs() {
    const periodType = document.getElementById('period_type').value;
    const presetPeriod = document.getElementById('preset-period');
    const customDates = document.getElementById('custom-dates');
    
    if (periodType === 'custom') {
        presetPeriod.style.display = 'none';
        customDates.style.display = 'block';
    } else {
        presetPeriod.style.display = 'block';
        customDates.style.display = 'none';
    }
}

// Set default dates when custom is selected
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (!dateFrom.value && !dateTo.value) {
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        
        dateTo.value = today.toISOString().split('T')[0];
        dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
    }
});
</script>
@endsection