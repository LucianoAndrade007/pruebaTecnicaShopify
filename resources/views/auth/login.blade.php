@extends('layouts.app')

@section('title', 'Login - Amplifica')

@section('content')
<div class="container">
    <div class="row justify-content-center" style="min-height: 80vh;">
        <div class="col-md-6 col-lg-4 d-flex align-items-center">
            <div class="card w-100">
                <div class="card-header text-center bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-store"></i> Amplifica
                    </h4>
                    <small>Integración Shopify</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input 
                                type="email" 
                                class="form-control @error('email') is-invalid @enderror" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus
                                placeholder="admin@test.com"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input 
                                type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                required
                                placeholder="password123"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>
                        <strong>Credenciales de prueba:</strong><br>
                        Email: admin@test.com<br>
                        Password: password123
                    </small>
                    <hr class="my-2">
                    <small>
                        ¿No tienes una cuenta? 
                        <a href="{{ route('register') }}" class="text-decoration-none">
                            <i class="fas fa-user-plus"></i> Regístrate
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection