@extends('template') 

@section('titulo', 'Error') 

@section('contenido') 

<body>

    <div class="container">

        <div class="icon">
            <i class="fa-solid fa-person-digging"></i>
        </div>

        <div class="status">
            <i class="fa-solid fa-circle"></i>
            En desarrollo
        </div>

        <h1>Esta función aún no está disponible</h1>

        <p>
            Estamos trabajando en esta sección para ofrecerte
            una mejor experiencia dentro del sistema.
            <br>
            Estará disponible próximamente.
        </p>

        <a href="{{ url()->previous() }}" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Regresar
        </a>

        <div class="footer">
            Proyecto San Miguel · Sistema de Gestión Lotificadora
        </div>

    </div>

</body>
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