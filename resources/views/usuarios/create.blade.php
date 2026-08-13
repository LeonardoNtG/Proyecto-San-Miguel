@extends('template') 

@section('titulo', 'Listado de Clientes') 

@section('contenido') 

<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

<div class="container-fluid px-4 py-3">
    
    {{-- Encabezado con Botón Volver --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Registrar Nuevo Usuario</h3>
            <p class="text-muted small mb-0">Asigna credenciales para el nuevo integrante del sistema</p>
        </div>
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <form action="{{ route('usuarios.store') }}" method="POST" id="formCreateUsuario">
                        @csrf

                        {{-- Nombre Completo --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="form-control border-start-0 @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" 
                                       placeholder="Ej. Carlos Mendoza" 
                                       required 
                                       autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Correo Electrónico --}}
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" 
                                       placeholder="ejemplo@dominio.com" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            {{-- Contraseña --}}
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                           placeholder="Mínimo 8 caracteres" 
                                           required>
                                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Confirmar Contraseña --}}
                            <div class="col-md-6 mb-4">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="password_confirmation" 
                                           class="form-control border-start-0 border-end-0" 
                                           placeholder="Repite la contraseña" 
                                           required>
                                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="password_confirmation">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="text-muted my-4">

                        {{-- Botones de Acción --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-light px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnGuardar">
                                <i class="fas fa-save me-1"></i> Guardar Usuario
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/usuarios-form.js') }}"></script>
@endsection

@section('scripts')
    <script>
            <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    </script>
    
@endsection