@extends('layouts.app')

@section('title', 'Dashboard - Amplifica')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="me-3">
            <small class="text-muted">Usuario: {{ session('user.name') }} | Tienda: {{ $storeInfo['store_name'] ?? 'Shopify' }}</small>
            @if($isConnected)
                <span class="badge bg-success ms-2">Conectado a Shopify</span>
            @else
                <span class="badge bg-warning ms-2">Sin Conexión</span>
            @endif
            @if(isset($productStats['low_stock_count']) && $productStats['low_stock_count'] > 0)
                <button type="button" class="btn btn-warning btn-sm ms-2 position-relative" onclick="showLowStockNotifications()">
                    <i class="fas fa-exclamation-triangle"></i>
                    Stock Bajo
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="lowStockBadge">
                        {{ $productStats['low_stock_count'] }}
                    </span>
                </button>
            @endif
        </div>
        <div class="auto-refresh-controls">
            <div class="input-group input-group-sm">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshData()" id="refreshBtn">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i> Actualizar
                </button>
                <select class="form-select form-select-sm" id="autoRefreshInterval" style="width: 140px;">
                    <option value="0">Sin auto-refresh</option>
                    <option value="30">Cada 30 seg</option>
                    <option value="60" selected>Cada 1 min</option>
                    <option value="120">Cada 2 min</option>
                    <option value="300">Cada 5 min</option>
                </select>
                <span class="badge bg-secondary ms-2" id="refreshStatus">Próximo: <span id="countdown">60s</span></span>
                <small class="text-muted ms-2" id="lastUpdate">Actualizado: {{ now()->format('H:i:s') }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Main Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Productos Totales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_products">{{ number_format($productStats['total_products'] ?? 0) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-check text-success"></i> <span data-stat="in_stock">{{ $productStats['in_stock'] ?? 0 }}</span> en stock
                    <i class="fas fa-times text-danger ml-2"></i> <span data-stat="out_of_stock">{{ $productStats['out_of_stock'] ?? 0 }}</span> agotado
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ingresos (30 días)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_revenue">
                            {{ isset($orderStats['total_revenue']) ? '$' . number_format($orderStats['total_revenue'], 0) : '$0' }}
                            {{ strtoupper($storeInfo['currency'] ?? 'CLP') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-shopping-cart"></i> <span data-stat="total_orders">{{ $orderStats['total_orders'] ?? 0 }}</span> pedidos
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Valor Promedio Pedido</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="avg_order_value">
                            {{ isset($orderStats['avg_order_value']) ? '$' . number_format($orderStats['avg_order_value'], 0) : '$0' }}
                            {{ strtoupper($storeInfo['currency'] ?? 'CLP') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calculator fa-2x text-gray-300"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-boxes"></i> <span data-stat="total_items">{{ $orderStats['total_items'] ?? 0 }}</span> artículos vendidos
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Exportaciones</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_exports">{{ number_format($exportStats['total_exports'] ?? 0) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-download fa-2x text-gray-300"></i>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-check text-success"></i> <span data-stat="successful_exports">{{ $exportStats['successful_exports'] ?? 0 }}</span> exitosas
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Products by Category Chart -->
    <div class="col-xl-6 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Productos por Categoría</h6>
            </div>
            <div class="card-body" style="height: 350px;">
                @if(isset($productStats['categories']) && $productStats['categories']->count() > 1)
                    <canvas id="categoriesChart"></canvas>
                @elseif(isset($productStats['categories']) && $productStats['categories']->count() == 1)
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                        <p class="text-muted"><strong>{{ $productStats['total_products'] }} productos encontrados</strong></p>
                        <p class="text-muted">Categoría: <strong>{{ $productStats['categories']->keys()->first() ?: 'Sin categoría' }}</strong></p>
                        <small class="text-muted">Agrega más tipos de producto en Shopify para ver el gráfico de categorías</small>
                    </div>
                @elseif(isset($productStats['total_products']) && $productStats['total_products'] > 0)
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                        <p class="text-muted"><strong>{{ $productStats['total_products'] }} productos encontrados</strong></p>
                        <p class="text-muted">Agrega tipos de producto a tus productos en Shopify para ver el gráfico</p>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay productos disponibles</p>
                        <small class="text-muted">Agrega productos en tu tienda Shopify</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Orders by Status Chart -->
    <div class="col-xl-6 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Pedidos por Estado</h6>
            </div>
            <div class="card-body" style="height: 350px;">
                @if(isset($orderStats['orders_by_status']) && $orderStats['orders_by_status']->isNotEmpty())
                    <canvas id="ordersStatusChart"></canvas>
                @elseif(isset($orderStats['total_orders']) && $orderStats['total_orders'] > 0)
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted"><strong>{{ $orderStats['total_orders'] }} pedidos encontrados</strong></p>
                        <p class="text-muted">Estados: En proceso...</p>
                        <small class="text-muted">El gráfico se actualizará con más pedidos</small>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-doughnut fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay pedidos disponibles</p>
                        <small class="text-muted">Los pedidos aparecerán aquí cuando tengas ventas</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Daily Revenue Chart -->
@if(isset($orderStats['total_orders']) && $orderStats['total_orders'] > 0)
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    Resumen de Ventas (Últimos 30 días)
                    <small class="text-muted">- {{ $orderStats['total_orders'] }} pedidos</small>
                </h6>
            </div>
            <div class="card-body" style="height: 400px;">
                @if(isset($orderStats['daily_revenue']) && $orderStats['daily_revenue']->isNotEmpty() && $orderStats['daily_revenue']->count() > 1)
                    <canvas id="dailyRevenueChart"></canvas>
                @else
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-success">${{ number_format($orderStats['total_revenue'] ?? 0) }}</h4>
                                    <p class="mb-0">Ingresos Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-info">${{ number_format($orderStats['avg_order_value'] ?? 0) }}</h4>
                                    <p class="mb-0">Valor Promedio</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-primary">{{ $orderStats['total_items'] ?? 0 }}</h4>
                                    <p class="mb-0">Items Vendidos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">El gráfico de tendencias aparecerá con más ventas en el tiempo</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top Productos Más Vendidos y Mayores Ingresos -->
@if($isConnected && (!empty($mostSoldProducts) || !empty($highestRevenueProducts)))
<div class="row mb-4">
    @if(!empty($mostSoldProducts))
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-trophy me-2"></i>Productos Más Vendidos (Últimos 30 días)
                </h6>
                <span class="badge bg-info">Top {{ count($mostSoldProducts) }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-center">Ingresos</th>
                                <th class="text-center">Órdenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $position = 1; @endphp
                            @foreach($mostSoldProducts as $product)
                            <tr>
                                <td>
                                    @if($position <= 3)
                                        @if($position == 1)
                                            <i class="fas fa-medal text-warning"></i>
                                        @elseif($position == 2)
                                            <i class="fas fa-medal text-secondary"></i>
                                        @else
                                            <i class="fas fa-medal text-info"></i>
                                        @endif
                                    @else
                                        {{ $position }}
                                    @endif
                                </td>
                                <td>
                                    <strong class="small">{{ Str::limit($product['product_name'], 20) }}</strong>
                                </td>
                                <td>
                                    <code class="text-muted small">{{ Str::limit($product['sku'] ?: 'N/A', 10) }}</code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ number_format($product['total_quantity_sold']) }}</span>
                                </td>
                                <td class="text-center">
                                    <strong class="text-success small">${{ number_format($product['total_revenue'], 0) }}</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $product['order_count'] }}</span>
                                </td>
                            </tr>
                            @php $position++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    @if(!empty($highestRevenueProducts))
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-dollar-sign me-2"></i>Productos con Mayor Ganancia (Últimos 30 días)
                </h6>
                <span class="badge bg-success">Top {{ count($highestRevenueProducts) }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th class="text-center">Ingresos</th>
                                <th class="text-center">Precio Prom.</th>
                                <th class="text-center">Órdenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $position = 1; @endphp
                            @foreach($highestRevenueProducts as $product)
                            <tr>
                                <td>
                                    @if($position <= 3)
                                        @if($position == 1)
                                            <i class="fas fa-medal text-warning"></i>
                                        @elseif($position == 2)
                                            <i class="fas fa-medal text-secondary"></i>
                                        @else
                                            <i class="fas fa-medal text-info"></i>
                                        @endif
                                    @else
                                        {{ $position }}
                                    @endif
                                </td>
                                <td>
                                    <strong class="small">{{ Str::limit($product['product_name'], 20) }}</strong>
                                </td>
                                <td>
                                    <code class="text-muted small">{{ Str::limit($product['sku'] ?: 'N/A', 10) }}</code>
                                </td>
                                <td class="text-center">
                                    <strong class="text-success">${{ number_format($product['total_revenue'], 0) }}</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark small">${{ number_format($product['avg_price'] ?? 0, 0) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $product['order_count'] }}</span>
                                </td>
                            </tr>
                            @php $position++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
@endif

<!-- Store Info -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Información de la Tienda
                    @if($isConnected)
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-check"></i> Conectado
                        </span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <strong>Tienda:</strong><br>
                        <span class="text-muted">{{ $storeInfo['store_name'] ?? 'Shopify Store' }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>Dominio:</strong><br>
                        <span class="text-muted">{{ $storeInfo['domain'] ?? config('services.shopify.shop_domain') }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>Moneda:</strong><br>
                        <span class="text-muted">{{ strtoupper($storeInfo['currency'] ?? 'CLP') }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>Plan:</strong><br>
                        <span class="text-muted">{{ $storeInfo['plan'] ?? 'Shopify' }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>Productos:</strong><br>
                        <span class="text-success">{{ $productStats['total_products'] ?? 0 }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>Órdenes (30d):</strong><br>
                        <span class="text-success">{{ $orderStats['total_orders'] ?? 0 }}</span>
                    </div>
                </div>
                
                @if($isConnected && (isset($productStats['total_products']) && $productStats['total_products'] == 0))
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>¡Tienda conectada exitosamente!</strong> 
                    Para ver gráficos y estadísticas, agrega algunos productos a tu tienda Shopify.
                </div>
                @endif
                
                @if($isConnected && isset($orderStats['total_orders']) && $orderStats['total_orders'] == 0)
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-shopping-cart"></i>
                    <strong>Sin pedidos aún.</strong> 
                    Las estadísticas de ventas aparecerán cuando recibas tu primera orden.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(isset($productStats['categories']) && $productStats['categories']->count() > 1)
    // Gráfico de Categorías
    const categoriesCanvas = document.getElementById('categoriesChart');
    if (categoriesCanvas) {
        const categoriesCtx = categoriesCanvas.getContext('2d');
        new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($productStats['categories']->keys()) !!},
                datasets: [{
                    data: {!! json_encode($productStats['categories']->values()) !!},
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 13
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                layout: {
                    padding: 20
                }
            }
        });
    }
    @endif

    @if(isset($orderStats['orders_by_status']) && $orderStats['orders_by_status']->isNotEmpty())
    // Gráfico de Estado de Pedidos
    const ordersStatusCanvas = document.getElementById('ordersStatusChart');
    if (ordersStatusCanvas) {
        const ordersStatusCtx = ordersStatusCanvas.getContext('2d');
    new Chart(ordersStatusCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($orderStats['orders_by_status']->keys()->map(function($status) { 
                $translations = [
                    'pending' => 'Pendiente',
                    'confirmed' => 'Confirmado', 
                    'shipped' => 'Enviado',
                    'delivered' => 'Entregado',
                    'cancelled' => 'Cancelado',
                    'refunded' => 'Reembolsado',
                    'processing' => 'Procesando',
                    'open' => 'Abierto',
                    'closed' => 'Cerrado',
                    'fulfillment_requested' => 'Cumplimiento Solicitado',
                    'unfulfilled' => 'Sin Cumplir',
                    'partial' => 'Parcial',
                    'fulfilled' => 'Cumplido'
                ];
                return $translations[$status] ?? ucfirst($status); 
            })) !!},
            datasets: [{
                data: {!! json_encode($orderStats['orders_by_status']->values()) !!},
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#858796'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 13
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            layout: {
                padding: 20
            }
        }
    });
    }
    @endif

    @if(isset($orderStats['daily_revenue']) && $orderStats['daily_revenue']->isNotEmpty())
    // Gráfico de Ingresos Diarios
    const dailyRevenueCanvas = document.getElementById('dailyRevenueChart');
    if (dailyRevenueCanvas) {
        const dailyRevenueCtx = dailyRevenueCanvas.getContext('2d');
    new Chart(dailyRevenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($orderStats['daily_revenue']->keys()) !!},
            datasets: [{
                label: 'Ingresos Diarios ({{ strtoupper($storeInfo['currency'] ?? 'CLP') }})',
                data: {!! json_encode($orderStats['daily_revenue']->values()) !!},
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        font: {
                            size: 13
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            layout: {
                padding: 10
            },
            elements: {
                point: {
                    radius: 4,
                    hoverRadius: 6
                },
                line: {
                    borderWidth: 3
                }
            }
        }
    });
    }
    @endif
});

// Auto-refresh functionality
let autoRefreshTimer = null;
let countdownTimer = null;
let currentInterval = 60; // Default 1 minute
let countdownSeconds = 0;

// Initialize auto-refresh
document.addEventListener('DOMContentLoaded', function() {
    // Load saved interval from localStorage
    const savedInterval = localStorage.getItem('dashboardAutoRefresh');
    if (savedInterval !== null) {
        currentInterval = parseInt(savedInterval);
        document.getElementById('autoRefreshInterval').value = currentInterval;
    }
    
    // Start auto-refresh if interval > 0
    if (currentInterval > 0) {
        startAutoRefresh();
    }
    
    // Handle interval change
    document.getElementById('autoRefreshInterval').addEventListener('change', function() {
        currentInterval = parseInt(this.value);
        localStorage.setItem('dashboardAutoRefresh', currentInterval);
        
        stopAutoRefresh();
        if (currentInterval > 0) {
            startAutoRefresh();
        }
    });
});

function startAutoRefresh() {
    stopAutoRefresh();
    
    if (currentInterval <= 0) {
        document.getElementById('refreshStatus').style.display = 'none';
        return;
    }
    
    document.getElementById('refreshStatus').style.display = 'inline-block';
    countdownSeconds = currentInterval;
    updateCountdown();
    
    // Start countdown timer
    countdownTimer = setInterval(updateCountdown, 1000);
    
    // Start refresh timer
    autoRefreshTimer = setInterval(function() {
        refreshData();
        countdownSeconds = currentInterval; // Reset countdown
    }, currentInterval * 1000);
}

function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function updateCountdown() {
    if (countdownSeconds <= 0) {
        countdownSeconds = currentInterval;
    }
    
    const minutes = Math.floor(countdownSeconds / 60);
    const seconds = countdownSeconds % 60;
    const display = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
    
    document.getElementById('countdown').textContent = display;
    countdownSeconds--;
}

function refreshData() {
    const refreshBtn = document.getElementById('refreshBtn');
    const refreshIcon = document.getElementById('refreshIcon');
    
    // Show loading state
    refreshBtn.disabled = true;
    refreshIcon.classList.add('fa-spin');
    
    // Make AJAX request
    fetch('{{ route("dashboard.data") }}', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateDashboardData(data);
            
            // Actualizar notificaciones de stock bajo
            if (data.productStats) {
                updateLowStockNotifications(data.productStats);
            }
            
            // showNotification('Datos actualizados correctamente', 'success');
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error updating dashboard:', error);
        showNotification('Error al actualizar los datos: ' + error.message, 'error');
    })
    .finally(() => {
        // Remove loading state
        refreshBtn.disabled = false;
        refreshIcon.classList.remove('fa-spin');
        
        // Update countdown
        countdownSeconds = currentInterval;
    });
}

function updateDashboardData(data) {
    // Update connection status
    const connectionBadge = document.querySelector('.badge.bg-success, .badge.bg-warning');
    if (connectionBadge) {
        if (data.isConnected) {
            connectionBadge.className = 'badge bg-success ms-2';
            connectionBadge.textContent = 'Conectado a Shopify';
        } else {
            connectionBadge.className = 'badge bg-warning ms-2';
            connectionBadge.textContent = 'Sin Conexión';
        }
    }

    // Update statistics cards
    if (data.productStats) {
        updateStatCard('total_products', data.productStats.total_products || 0);
        updateStatCard('in_stock', data.productStats.in_stock || 0);
        updateStatCard('out_of_stock', data.productStats.out_of_stock || 0);
    }

    if (data.orderStats) {
        updateStatCard('total_revenue', '$' + formatNumber(data.orderStats.total_revenue || 0));
        updateStatCard('total_orders', data.orderStats.total_orders || 0);
        updateStatCard('avg_order_value', '$' + formatNumber(data.orderStats.avg_order_value || 0));
        updateStatCard('total_items', data.orderStats.total_items || 0);
    }

    if (data.exportStats) {
        updateStatCard('total_exports', data.exportStats.total_exports || 0);
        updateStatCard('successful_exports', data.exportStats.successful_exports || 0);
    }

    // Update store info
    if (data.storeInfo && data.storeInfo.store_name) {
        const storeElements = document.querySelectorAll('small.text-muted');
        storeElements.forEach(elem => {
            if (elem.textContent.includes('Tienda:')) {
                elem.innerHTML = elem.innerHTML.replace(/Tienda: [^|]+/, 'Tienda: ' + data.storeInfo.store_name);
            }
        });
    }

    // Update last update time
    const lastUpdateElement = document.getElementById('lastUpdate');
    if (lastUpdateElement && data.lastUpdate) {
        const now = new Date();
        lastUpdateElement.textContent = 'Actualizado: ' + now.toLocaleTimeString();
    }
    
    // Update most sold products table
    updateMostSoldProductsTable(data.mostSoldProducts || []);
    
    // Update highest revenue products table
    updateHighestRevenueProductsTable(data.highestRevenueProducts || []);
    
    // Update charts if necessary
    updateCharts(data);
}

function updateCharts(data) {
    // Update categories chart
    if (data.productStats && data.productStats.categories) {
        const categoriesCount = Object.keys(data.productStats.categories).length;
        const categoriesCardBody = document.querySelector('#categoriesChart')?.parentElement;
        
        if (categoriesCardBody) {
            if (categoriesCount > 1) {
                // Show chart - need to recreate it with new data
                categoriesCardBody.innerHTML = '<canvas id="categoriesChart"></canvas>';
                
                const categoriesCanvas = document.getElementById('categoriesChart');
                if (categoriesCanvas) {
                    const categoriesCtx = categoriesCanvas.getContext('2d');
                new Chart(categoriesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(data.productStats.categories),
                        datasets: [{
                            data: Object.values(data.productStats.categories),
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                                '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: { size: 13 },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.parsed;
                                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        let percentage = Math.round((value / total) * 100);
                                        return label + ': ' + value + ' unidades (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        layout: { padding: 20 }
                    }
                });
                }
            } else if (categoriesCount === 1) {
                // Show single category message
                const categoryName = Object.keys(data.productStats.categories)[0] || 'Sin categoría';
                const productCount = data.productStats.total_products || 0;
                categoriesCardBody.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                        <p class="text-muted"><strong>${productCount} productos encontrados</strong></p>
                        <p class="text-muted">Categoría: <strong>${categoryName}</strong></p>
                        <small class="text-muted">Agrega más tipos de producto en Shopify para ver el gráfico de categorías</small>
                    </div>
                `;
            }
        }
    }
    
    // Update orders status chart  
    if (data.orderStats && data.orderStats.orders_by_status) {
        const ordersStatusCount = Object.keys(data.orderStats.orders_by_status).length;
        const ordersCardBody = document.querySelector('#ordersStatusChart')?.parentElement;
        
        if (ordersCardBody && ordersStatusCount > 0) {
            ordersCardBody.innerHTML = '<canvas id="ordersStatusChart"></canvas>';
            
            const ordersStatusCanvas = document.getElementById('ordersStatusChart');
            if (ordersStatusCanvas) {
                const ordersStatusCtx = ordersStatusCanvas.getContext('2d');
            new Chart(ordersStatusCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(data.orderStats.orders_by_status).map(status => {
                        const translations = {
                            'pending': 'Pendiente',
                            'confirmed': 'Confirmado', 
                            'shipped': 'Enviado',
                            'delivered': 'Entregado',
                            'cancelled': 'Cancelado',
                            'refunded': 'Reembolsado',
                            'processing': 'Procesando',
                            'open': 'Abierto',
                            'closed': 'Cerrado',
                            'fulfillment_requested': 'Cumplimiento Solicitado',
                            'unfulfilled': 'Sin Cumplir',
                            'partial': 'Parcial',
                            'fulfilled': 'Cumplido'
                        };
                        return translations[status] || status.charAt(0).toUpperCase() + status.slice(1);
                    }),
                    datasets: [{
                        data: Object.values(data.orderStats.orders_by_status),
                        backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#858796'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: { size: 13 },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = Math.round((value / total) * 100);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    layout: { padding: 20 }
                }
            });
            }
        }
    }
}

function updateMostSoldProductsTable(products) {
    const tableContainer = document.querySelector('.row.mb-4:has(.card .fas.fa-trophy)');
    
    if (!products || !Array.isArray(products) || products.length === 0) {
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
        return;
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'block';
        const tbody = tableContainer.querySelector('tbody');
        const badge = tableContainer.querySelector('.badge.bg-info');
        
        if (tbody && badge) {
            badge.textContent = `Top ${products.length}`;
            
            let html = '';
            products.forEach((product, index) => {
                const position = index + 1;
                let positionIcon = position;
                
                if (position <= 3) {
                    if (position === 1) {
                        positionIcon = '<i class="fas fa-medal text-warning"></i>';
                    } else if (position === 2) {
                        positionIcon = '<i class="fas fa-medal text-secondary"></i>';
                    } else {
                        positionIcon = '<i class="fas fa-medal text-info"></i>';
                    }
                }
                
                const productName = product.product_name.length > 20 ? product.product_name.substring(0, 20) + '...' : product.product_name;
                const sku = (product.sku || 'N/A').length > 10 ? (product.sku || 'N/A').substring(0, 10) + '...' : (product.sku || 'N/A');
                
                html += `
                    <tr>
                        <td>${positionIcon}</td>
                        <td><strong class="small">${productName}</strong></td>
                        <td><code class="text-muted small">${sku}</code></td>
                        <td class="text-center"><span class="badge bg-success">${new Intl.NumberFormat().format(product.total_quantity_sold)}</span></td>
                        <td class="text-center"><strong class="text-success small">$${new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(product.total_revenue)}</strong></td>
                        <td class="text-center"><span class="badge bg-primary">${product.order_count}</span></td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
    }
}

function updateHighestRevenueProductsTable(products) {
    const tableContainer = document.querySelector('.row.mb-4:has(.card .fas.fa-dollar-sign)');
    
    if (!products || !Array.isArray(products) || products.length === 0) {
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
        return;
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'block';
        const tbody = tableContainer.querySelector('tbody');
        const badge = tableContainer.querySelector('.badge.bg-success');
        
        if (tbody && badge) {
            badge.textContent = `Top ${products.length}`;
            
            let html = '';
            products.forEach((product, index) => {
                const position = index + 1;
                let positionIcon = position;
                
                if (position <= 3) {
                    if (position === 1) {
                        positionIcon = '<i class="fas fa-medal text-warning"></i>';
                    } else if (position === 2) {
                        positionIcon = '<i class="fas fa-medal text-secondary"></i>';
                    } else {
                        positionIcon = '<i class="fas fa-medal text-info"></i>';
                    }
                }
                
                const productName = product.product_name.length > 20 ? product.product_name.substring(0, 20) + '...' : product.product_name;
                const sku = (product.sku || 'N/A').length > 10 ? (product.sku || 'N/A').substring(0, 10) + '...' : (product.sku || 'N/A');
                
                html += `
                    <tr>
                        <td>${positionIcon}</td>
                        <td><strong class="small">${productName}</strong></td>
                        <td><code class="text-muted small">${sku}</code></td>
                        <td class="text-center"><strong class="text-success">$${new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(product.total_revenue)}</strong></td>
                        <td class="text-center"><span class="badge bg-warning text-dark small">$${new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(product.avg_price || 0)}</span></td>
                        <td class="text-center"><span class="badge bg-primary">${product.order_count}</span></td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
    }
}

function updateStatCard(key, value) {
    // Find elements that might contain this statistic
    const selectors = [
        `[data-stat="${key}"]`,
        `.stat-${key}`,
        `#${key}`
    ];
    
    let element = null;
    for (const selector of selectors) {
        element = document.querySelector(selector);
        if (element) break;
    }
    
    // If no specific element found, search by content
    if (!element) {
        const allStatElements = document.querySelectorAll('.h5.mb-0.font-weight-bold');
        allStatElements.forEach(el => {
            const card = el.closest('.card');
            if (card && card.textContent.toLowerCase().includes(key.replace('_', ' '))) {
                element = el;
            }
        });
    }
    
    if (element) {
        element.textContent = formatNumber(value);
    }
}

function formatNumber(value) {
    if (typeof value === 'string' && value.startsWith('$')) {
        return value;
    }
    return typeof value === 'number' ? value.toLocaleString() : value;
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.dashboard-notification');
    existing.forEach(n => n.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show dashboard-notification`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Add some CSS for better styling
const style = document.createElement('style');
style.textContent = `
    .auto-refresh-controls .input-group {
        align-items: center;
    }
    
    .auto-refresh-controls .form-select {
        border-radius: 0;
    }
    
    .auto-refresh-controls .btn {
        border-radius: 0.375rem 0 0 0.375rem;
    }
    
    .auto-refresh-controls .form-select:last-of-type {
        border-radius: 0 0.375rem 0.375rem 0;
    }
    
    #refreshStatus {
        font-size: 0.75rem;
        white-space: nowrap;
    }
    
    @media (max-width: 768px) {
        .auto-refresh-controls {
            margin-top: 10px;
            width: 100%;
        }
        
        .auto-refresh-controls .input-group {
            flex-wrap: nowrap;
        }
        
        .auto-refresh-controls .form-select {
            min-width: 120px;
        }
    }
`;
document.head.appendChild(style);

// Función de Notificaciones de Stock Bajo
function showLowStockNotifications() {
    const modal = document.getElementById('lowStockModal');
    const bootstrap_modal = new bootstrap.Modal(modal);
    bootstrap_modal.show();
}

// Actualizar notificaciones de stock bajo en actualizaciones AJAX
function updateLowStockNotifications(data) {
    const badge = document.getElementById('lowStockBadge');
    const button = badge?.closest('button');
    
    if (data.low_stock_count > 0) {
        if (badge) {
            badge.textContent = data.low_stock_count;
        }
        
        // Actualizar contenido del modal
        updateLowStockModal(data.low_stock_products);
        
        // Mostrar botón si está oculto
        if (button) {
            button.style.display = 'inline-block';
        }
    } else {
        // Ocultar botón si no hay stock bajo
        if (button) {
            button.style.display = 'none';
        }
    }
}

function updateLowStockModal(products) {
    const tbody = document.getElementById('lowStockTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        
        // Cantidad de stock con color de advertencia
        let stockClass = 'text-warning';
        if (product.stock_quantity <= 3) {
            stockClass = 'text-danger fw-bold';
        } else if (product.stock_quantity <= 5) {
            stockClass = 'text-warning fw-bold';
        }
        
        row.innerHTML = `
            <td>
                ${product.image ? `<img src="${product.image}" alt="${product.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" class="me-2">` : ''}
                <strong>${product.name}</strong>
            </td>
            <td><span class="badge bg-secondary">${product.category}</span></td>
            <td>
                <span class="${stockClass}">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    ${product.stock_quantity} unidades
                </span>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}
</script>

<!-- Low Stock Modal -->
<div class="modal fade" id="lowStockModal" tabindex="-1" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="lowStockModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Productos con Stock Bajo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        Los siguientes productos tienen menos de 10 unidades en stock. Considera reabastecer pronto.
                    </div>
                </div>
                
                @if(isset($productStats['low_stock_products']) && count($productStats['low_stock_products']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Stock Actual</th>
                                </tr>
                            </thead>
                            <tbody id="lowStockTableBody">
                                @foreach($productStats['low_stock_products'] as $product)
                                    <tr>
                                        <td>
                                            @if($product['image'])
                                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" 
                                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" class="me-2">
                                            @endif
                                            <strong>{{ $product['name'] }}</strong>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $product['category'] }}</span></td>
                                        <td>
                                            <span class="
                                                @if($product['stock_quantity'] <= 3) text-danger fw-bold 
                                                @elseif($product['stock_quantity'] <= 5) text-warning fw-bold 
                                                @else text-warning @endif
                                            ">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                {{ $product['stock_quantity'] }} unidades
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5 class="text-muted">¡Excelente!</h5>
                        <p class="text-muted">Todos los productos tienen stock suficiente.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="{{ route('products.index') }}" class="btn btn-primary">
                    <i class="fas fa-boxes me-1"></i>
                    Ver Todos los Productos
                </a>
            </div>
        </div>
    </div>
</div>

@endsection