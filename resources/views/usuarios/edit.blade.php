@extends('template') 

@section('titulo', 'Edicion de usuarios') 

@section('contenido') 

<link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">

<div class="container-fluid px-4 py-3">
    
    {{-- Encabezado con Botón Volver --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Editar Usuario</h3>
            <p class="text-muted small mb-0">Modifica la información o actualiza las credenciales de {{ $usuario->name }}</p>
        </div>
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" id="formEditUsuario">
                        @csrf
                        @method('PUT')

                        {{-- Nombre Completo --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="form-control border-start-0 @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $usuario->name) }}" 
                                       required 
                                       autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Correo Electrónico --}}
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $usuario->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Alerta informativa para la Contraseña --}}
                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4 py-2 px-3" role="alert">
                            <i class="fas fa-info-circle me-2 fa-lg"></i>
                            <div class="small">
                                Deja los campos de contraseña en blanco si *no deseas cambiar* la clave actual del usuario.
                            </div>
                        </div>

                        <div class="row">
                            {{-- Nueva Contraseña --}}
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                           placeholder="Opcional (min. 8 caract.)">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Confirmar Nueva Contraseña --}}
                            <div class="col-md-6 mb-4">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirmar Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="password_confirmation" 
                                           class="form-control border-start-0 border-end-0" 
                                           placeholder="Repite si la cambiaste">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="password_confirmation">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="text-muted my-4">

                        {{-- Selección de Rol --}}
                        <div class="mb-3">
                                <label for="role" class="form-label">Rol</label>
                                @if(auth()->id() === $usuario->id)

                            <select class="form-select" disabled>

                                @foreach($roles as $rol)

                                    @if($usuario->hasRole($rol->name))

                                        <option selected>
                                         {{ ucfirst($rol->name) }}
                                        </option>

                                    @endif

                                @endforeach

                            </select>

                            <input type="hidden" name="role" value="admin">

                                <div class="form-text">
                                    <i class="fas fa-lock me-1"></i>
                                 No puedes cambiar tu propio rol de administrador.
                                </div>

                            @else

                                <select name="role" id="role" class="form-select" required>

                                    @foreach($roles as $rol)

                                        <option value="{{ $rol->name }}"
                                            {{ $usuario->hasRole($rol->name) ? 'selected' : '' }}>

                                            {{ ucfirst($rol->name) }}

                                        </option>

                                    @endforeach

                                </select>

                            @endif
                        </div>
                        <hr class="text-muted my-4">                   

                        {{-- Selección de Lotificaciones --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Proyectos Asignados (Para Aislamiento)</label>
                            <p class="text-muted small mb-2">Selecciona los proyectos a los que este usuario tendrá acceso (Gerentes y Agentes).</p>
                            
                            <div class="row">
                                @foreach($lotificaciones as $lotificacion)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="lotificaciones[]" value="{{ $lotificacion->id }}" id="lot_{{ $lotificacion->id }}" {{ in_array($lotificacion->id, $assignedLotificaciones) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="lot_{{ $lotificacion->id }}">
                                            {{ $lotificacion->nombre }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Botones de Acción --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-light px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnActualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar Usuario
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reutilizamos el mismo JS de interacción con el ojito --}}
<script src="{{ asset('js/usuarios-form.js') }}"></script>
@endsection

@section('scripts')

    <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <script src="{{ asset('js/chartM.js') }}"></script>

    <script src="{{ asset('js/chartAD.js') }}"></script>

    <script src="{{ asset('js/chartPD.js') }}"></script>

@endsection