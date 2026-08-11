<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyecto San Miguel</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>

    <div class="background"></div>

    <div class="login-container">

        <img src="{{ asset('images/logo.png') }}" class="logo" alt="Proyecto San Miguel">

        <h2>Sistema de Gestión </h2>
                {{-- Errores de Validación --}}
             @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <ul class="mb-0 ps-3 small">
                         @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

        <form action="{{ route('login.post') }}" method="POST" id="loginForm">
            @csrf

            <div class="input-group">
                <i class="fa-regular fa-user"></i>
                <input type="email" name="email" id="email" placeholder="Ingrese su usuario">
            </div>

            <div class="input-group">

                <i class="fa-solid fa-lock"></i>

                <input
                    id="password"
                     type="password" name="password" id="password"
                    placeholder="Ingrese su contraseña">

                <button
                    type="button" 
                    id="togglePassword"
                    class="eye">

                    <i class="fa-regular fa-eye" id="eyeIcon"></i>

                </button>

            </div>

            <button type="submit" class="login-btn" id="btnSubmit">
                <span id="btnText"><i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión</span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>

        </form>

        <a href="#" class="forgot">
            <i class="fa-regular fa-id-card"></i>
            Solicitar cambio de credenciales
        </a>

        <div class="footer">
            <i class="fa-solid fa-shield-halved"></i>
            Acceso seguro al sistema
        </div>

    </div>

    
    <!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/login.js') }}"></script>
<script src="js/login.js"></script>
</body>

</html>