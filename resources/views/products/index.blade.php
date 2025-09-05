@extends('layouts.app')

@section('title', 'Productos - Amplifica')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-box"></i> Productos Shopify
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('products.export', ['format' => 'excel']) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
            <a href="{{ route('products.export', ['format' => 'csv']) }}" class="btn btn-info btn-sm">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="{{ route('products.export', ['format' => 'pdf']) }}" class="btn btn-danger btn-sm">
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
        <form method="GET" action="{{ route('products.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="category" class="form-label">Categoría</label>
                <select name="category" id="category" class="form-select">
                    <option value="">Todas las categorías</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label for="stock_level" class="form-label">Nivel de Stock</label>
                <select name="stock_level" id="stock_level" class="form-select">
                    <option value="">Todos los niveles</option>
                    <option value="out_of_stock" {{ request('stock_level') == 'out_of_stock' ? 'selected' : '' }}>Sin stock</option>
                    <option value="low_stock" {{ request('stock_level') == 'low_stock' ? 'selected' : '' }}>Stock bajo (&lt; 10)</option>
                    <option value="in_stock" {{ request('stock_level') == 'in_stock' ? 'selected' : '' }}>Con stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar Producto</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Nombre o SKU" value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="btn-group" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if($products && $products->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list"></i> Lista de Productos ({{ $products->count() }} productos)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>SKU</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Tipo/Vendor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr @if($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0) class="table-warning" @endif>
                            <td>
                                @if($product['image'])
                                    <img src="{{ $product['image'] }}" 
                                         alt="{{ $product['name'] }}" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <code>{{ $product['id'] }}</code>
                            </td>
                            <td>
                                @if($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0)
                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                @endif
                                <strong>{{ $product['name'] }}</strong>
                                @if($product['stock_quantity'] <= 3 && $product['stock_quantity'] > 0)
                                    <br><small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i><strong>Stock Crítico</strong></small>
                                @elseif($product['stock_quantity'] < 10 && $product['stock_quantity'] > 3)
                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Stock Bajo</small>
                                @endif
                            </td>
                            <td>
                                <code>{{ $product['sku'] }}</code>
                            </td>
                            <td>
                                <strong>${{ number_format($product['price'], 2) }}</strong>
                                @if($product['sale_price'])
                                    <br><small class="text-muted"><del>${{ number_format($product['regular_price'], 2) }}</del></small>
                                @endif
                            </td>
                            <td>
                                @if($product['stock_quantity'] <= 0)
                                    <span class="badge bg-secondary">0</span>
                                @elseif($product['stock_quantity'] <= 3)
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $product['stock_quantity'] }}
                                    </span>
                                @elseif($product['stock_quantity'] <= 5)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $product['stock_quantity'] }}
                                    </span>
                                @elseif($product['stock_quantity'] < 10)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $product['stock_quantity'] }}
                                    </span>
                                @else
                                    <span class="badge bg-success">{{ $product['stock_quantity'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if($product['stock_status'] == 'instock')
                                    <span class="badge bg-success">En Stock</span>
                                @else
                                    <span class="badge bg-danger">Sin Stock</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $product['categories'] ?: 'Sin tipo' }}
                                    @if($product['vendor'] ?? false)
                                        <br><strong>{{ $product['vendor'] }}</strong>
                                    @endif
                                </small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-8">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Los datos se obtienen en tiempo real desde la API de Shopify
                    </small>
                </div>
                <div class="col-md-4">
                    <small>
                        <strong>Leyenda de Stock:</strong><br>
                        <span class="badge bg-danger me-1">1-3</span> Stock Crítico
                        <span class="badge bg-warning me-1">4-9</span> Stock Bajo
                        <span class="badge bg-success">10+</span> Stock Normal
                    </small>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h4>No se encontraron productos</h4>
            <p class="text-muted">
                No hay productos disponibles o hay un problema con la conexión a Shopify.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
@endif
@endsection