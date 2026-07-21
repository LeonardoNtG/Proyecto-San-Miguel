@extends('template') {{-- 1. Hereda la plantilla principal --}}

@section('titulo', 'Inicio') {{-- 2. Define el contenido de la sección 'titulo' --}}

@section('contenido') {{-- 3. Abre la sección principal 'contenido' --}}

    <h1>Bienvenido!</h1>

<hr>


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