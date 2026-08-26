<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistema de lotificacion san Miguel">
    <meta name="author" content="Leonardo Cruz">

    <title>Lotificacion San Miguel @yield('titulo') </title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('css/font.css')}}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/template.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>


    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ asset('/inicio') }}">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Sistema de control<sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="{{ asset('/inicio') }}">
                    <i class="fa-solid fa-house"></i>
                    <span>General</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>Clientes</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Opciones de Clientes:  </h6>
                        <a class="collapse-item" href="{{ route('registro.index') }}">Clientes / Ventas</a>
                        <a class="collapse-item" href="{{ route('reservas.index') }}">Reservas</a>
                        <a class="collapse-item" href="{{ route('estados_cuenta') }}">Estados de cuenta</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Utilities Collapse Menu -->


            <!-- Divider -->
            <hr class="sidebar-divider">

            @role('admin')
            <!-- Heading -->
            <div class="sidebar-heading">
                Opciones
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages"
                    aria-expanded="true" aria-controls="collapsePages">
                    <i class="fas fa-fw fa-folder"></i>
                    <span>Registros</span>
                </a>
                <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header" href="{{ asset('/errores/post') }}">Reportes</h6>
                        <a class="collapse-item" href="{{ route('reportes.financiero') }}">Archivos</a>
                        <a class="collapse-item" href="{{ route('reportes.cierre_caja') }}">Cierre de Caja</a>
                        <a class="collapse-item" href="{{ route('reportes.index') }}">Egresos</a>
                        <a class="collapse-item" href="{{ route('dashboard.grafico') }}">Graficos</a>
                        <div class="collapse-divider"></div>
                        <h6 class="collapse-header">Seguridad</h6>
                        <a class="collapse-item" href="{{ route('auditoria.index') }}">Auditoría</a>
                    </div>
                </div>
            </li>
            @endrole

            <!-- Nav Item - Archivos Collapse Menu (solo Admin: gestiona Bloques y Lotes) -->
            @role('admin')
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseArchivos"
                    aria-expanded="true" aria-controls="collapseArchivos">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Archivos</span>
                </a>
                <div id="collapseArchivos" class="collapse" aria-labelledby="headingArchivos" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Lotificación:</h6>
                        <a class="collapse-item" href="{{ route('bloques.index') }}">Bloques y Lotes</a>
                    </div>
                </div>
            </li>
            @endrole

            <!-- Nav Item - Tables -->
            <li class="nav-item">
                @role('admin')
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseConfig"
                    aria-expanded="true" aria-controls="collapseConfig">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Configuraciones</span>
                </a>
                <div id="collapseConfig" class="collapse" aria-labelledby="headingConfig" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="{{ route('usuarios.index') }}">Usuarios</a>
                        <a class="collapse-item" href="{{ route('lotificaciones.index') }}">Proyectos (Recibos)</a>
                    </div>
                </div>
                @endrole
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>


        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
                        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                   
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>


                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - Lotificacion Selector -->
                        @if(isset($userLotificaciones) && $userLotificaciones->count() > 0)
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="lotificacionDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-building fa-fw"></i>
                                <span class="badge badge-primary badge-counter">
                                    {{ $userLotificaciones->firstWhere('id', $activeLotificacionId)->nombre ?? 'Seleccione' }}
                                </span>
                            </a>
                            <!-- Dropdown - Lotificaciones -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="lotificacionDropdown">
                                <h6 class="dropdown-header">
                                    Tus Lotificaciones
                                </h6>
                                @foreach($userLotificaciones as $lot)
                                    <form action="{{ route('lotificacion.setActiva', $lot->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="icon-circle {{ $activeLotificacionId == $lot->id ? 'bg-success' : 'bg-primary' }}">
                                                    <i class="fas fa-check text-white" style="{{ $activeLotificacionId == $lot->id ? '' : 'visibility: hidden;' }}"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="{{ $activeLotificacionId == $lot->id ? 'font-weight-bold' : '' }}">
                                                    {{ $lot->nombre }}
                                                </span>
                                            </div>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </li>
                        @endif

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Usuario</span>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Soporte
                                </a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                 @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                     <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </button>
                </form>
                            </div>
                        </li>
                        </li>
                    </ul>
 
                </nav>
                <!-- End of Topbar -->
                <div class="container-fluid">
                    @yield('contenido')
                 </div>


    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Listo para salir?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Selecciona "cerrar sesion" si ya has realizado todas tus tareas y reportes del dia.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-primary" href="login.html">Cerrar sesion</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('js/jqueryM.js') }}"></script>
    <script src="{{ asset('js/bootstrapBM.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('js/jqueryEM.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sbAdmin2M.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('js/chartM.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('js/chartAD.js') }}"></script>
    <script src="{{ asset('js/chartPD.js') }}"></script>
    @yield('scripts')
    <body id="page-top">


</body>

</html>