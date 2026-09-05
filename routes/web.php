<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloqueController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\GraficoController;
use App\Http\Controllers\LotificacionController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\PortalClienteController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\RescisionController;
use App\Http\Controllers\CuentaBancariaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Portal del Cliente (Público con protección de fuerza bruta / saturación)
Route::get('/mi-estado/{token}', [PortalClienteController::class, 'show'])
    ->name('portal.estado_cuenta')
    ->middleware('throttle:30,1');

// =========================================================================
// RUTAS AUTENTICADAS
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // Proyecto Activo y Rescisión
    Route::post('/lotificacion/{id}/activa', [LotificacionController::class, 'setLotificacionActiva'])->name('lotificacion.setActiva');
    Route::post('/ventas/{id}/rescindir', [VentaController::class, 'rescindir'])->name('ventas.rescindir');

    // Inicio / Dashboard Operativo y Gerencial
    Route::get('/inicio', [\App\Http\Controllers\HomeController::class, 'index'])->name('inicio');

    Route::get('/clientes', function () {
        return view('clientes');
    });

    // ---------------------------------------------------------------------
    // CONSULTAS, REPORTES Y EXPEDIENTES (100% LIBRES DE CAJA ABIERTA)
    // ---------------------------------------------------------------------
    // Listado y Expedientes de Clientes
    Route::get('registro', [ClienteController::class, 'index'])->name('registro.index');
    Route::get('registro/{cliente}', [ClienteController::class, 'show'])->name('registro.show');
    Route::get('registro/{cliente}/editar', [ClienteController::class, 'edit'])->name('registro.edit');
    Route::put('registro/{cliente}', [ClienteController::class, 'update'])->name('registro.update');
    Route::delete('registro/{cliente}', [ClienteController::class, 'destroy'])->name('registro.destroy');

    // Estados de cuenta
    Route::get('/estados-de-cuenta', [ClienteController::class, 'estadosCuenta'])->name('estados_cuenta');

    // Listado y consulta de Reservas
    Route::get('reservas', [ReservaController::class, 'index'])->name('reservas.index');
    Route::get('reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');

    // Historial de Rescisiones y Desistimientos de Lotes
    Route::get('rescisiones', [RescisionController::class, 'index'])->name('rescisiones.index');

    // Dashboard de Gráficos y Estadísticas
    Route::get('/dashboard-grafico', [GraficoController::class, 'dashboard'])->name('dashboard.grafico');

    // Impresión de Recibos y Documentos
    Route::get('abono/{abono_id}/imprimir', [AbonoController::class, 'imprimirRecibo'])->name('imprimirRecibo');
    Route::get('abonos/{abono_id}/imprimir', [AbonoController::class, 'imprimirRecibo'])->name('abonos.imprimir');
    Route::post('cuotas/{cuota}/exonerar-mora', [CuotaController::class, 'exonerarMora'])->name('cuotas.exonerarMora');

    // Módulo de Caja (Arqueo, Apertura, Cierre de Turno y Reporte Diario)
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::post('abrir-caja', [ReporteController::class, 'abrirCaja'])->name('abrirCaja');
        Route::post('cerrar-caja', [ReporteController::class, 'cerrarCaja'])->name('cerrarCaja');
        Route::get('cierre-caja', [ReportesController::class, 'cierreCaja'])->name('cierre_caja');
        Route::get('cierre-caja/pdf', [ReportesController::class, 'imprimirCierreCajaPdf'])->name('cierre_caja.pdf');
        Route::get('cierre-turno/{id}/pdf', [ReporteController::class, 'imprimirCierreTurnoPdf'])->name('cierre_turno.pdf');
        Route::delete('salidas/{id}', [ReporteController::class, 'destroy'])->name('destroy');
    });

    // APIs para Selects Dinámicos
    Route::get('/api/bloques/{bloque}/lotes', [LoteController::class, 'getLotesByBloque'])->name('api.lotes.by.bloque');
    Route::get('/api/lotificaciones/{lotificacion}/bloques', [BloqueController::class, 'getBloquesByLotificacion'])->name('api.bloques.by.lotificacion');
    Route::get('/api/cuentas-bancarias', [CuentaBancariaController::class, 'index'])->name('api.cuentas_bancarias.index');
    Route::post('/api/cuentas-bancarias', [CuentaBancariaController::class, 'store'])->name('api.cuentas_bancarias.store');

    // ---------------------------------------------------------------------
    // OPERACIONES TRANSACCIONALES QUE INGRESAN/EGRESAN DINERO (PROTEGIDAS POR CAJA)
    // ---------------------------------------------------------------------
    Route::middleware(['caja.abierta'])->group(function () {
        // Crear Venta / Contrato (Recibe Prima)
        Route::get('registro/nuevo/crear', [ClienteController::class, 'create'])->name('registro.create');
        Route::post('registro', [ClienteController::class, 'store'])->name('registro.store');

        // Cobrar Abonos / Cuotas (Recibe Dinero)
        Route::resource('abonos', AbonoController::class)->only(['index', 'create', 'store']);
        Route::prefix('abono/{cliente}')->name('abono.')->group(function () {
            Route::get('registrar', [AbonoController::class, 'create'])->name('create');
            Route::post('/', [AbonoController::class, 'store'])->name('store');
        });

        // Crear / Formalizar Reservas (Recibe Dinero)
        Route::get('reservas-crear/nueva', [ReservaController::class, 'create'])->name('reservas.create');
        Route::post('reservas', [ReservaController::class, 'store'])->name('reservas.store');
        Route::post('reservas/{reserva}/anular', [ReservaController::class, 'anular'])->name('reservas.anular');
        Route::get('reservas/{reserva}/formalizar', [ReservaController::class, 'formalizar'])->name('reservas.formalizar');
        Route::post('reservas/{reserva}/formalizar', [ReservaController::class, 'procesarFormalizacion'])->name('reservas.procesarFormalizacion');

        // Registrar Salidas / Gastos de Caja
        Route::post('reportes/salidas', [ReporteController::class, 'store'])->name('reportes.store');
    });

    // ---------------------------------------------------------------------
    // GESTIÓN DE PROYECTOS E INVENTARIO (ADMINISTRADOR / GESTOR LOTIFICACIONES)
    // ---------------------------------------------------------------------
    Route::middleware(['permission:gestionar-lotificaciones|role:Administrador'])->group(function () {
        Route::resource('bloques', BloqueController::class);
        Route::match(['put', 'patch', 'post'], 'bloques/{bloque}', [BloqueController::class, 'update']);
        Route::prefix('bloques/{bloque}/lotes')->name('lotes.')->group(function () {
            Route::get('/', [LoteController::class, 'index'])->name('index');
            Route::get('crear', [LoteController::class, 'create'])->name('create');
            Route::post('/', [LoteController::class, 'store'])->name('store');
            Route::post('generar-masivo', [LoteController::class, 'generarMasivo'])->name('generar_masivo');
        });
        Route::prefix('lotes')->name('lotes.')->group(function () {
            Route::get('{lote}/editar', [LoteController::class, 'edit'])->name('edit');
            Route::put('{lote}', [LoteController::class, 'update'])->name('update');
            Route::delete('{lote}', [LoteController::class, 'destroy'])->name('destroy');
        });
        Route::resource('lotificaciones', LotificacionController::class);
    });

    // ---------------------------------------------------------------------
    // REPORTES FINANCIEROS, EJECUTIVOS Y ANALÍTICA (ADMINISTRADOR Y GERENCIA)
    // ---------------------------------------------------------------------
    Route::middleware(['role:Administrador|Gerente'])->group(function () {
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('financiero', [ReporteController::class, 'financiero'])->name('financiero');
            Route::get('financiero/pdf', [ReporteController::class, 'financieroPdf'])->name('financiero.pdf');
            Route::get('financiero/excel', [ReporteController::class, 'financieroExcel'])->name('financiero.excel');

            // Inventario de Lotes
            Route::get('inventario-lotes', [App\Http\Controllers\ReportesAvanzadosController::class, 'inventarioLotes'])->name('inventario_lotes');
            Route::get('inventario-lotes/pdf', [App\Http\Controllers\ReportesAvanzadosController::class, 'inventarioLotesPdf'])->name('inventario_lotes.pdf');
            Route::get('inventario-lotes/excel', [App\Http\Controllers\ReportesAvanzadosController::class, 'inventarioLotesExcel'])->name('inventario_lotes.excel');

            // Cartera de Clientes y Abonos
            Route::get('cartera-clientes', [App\Http\Controllers\ReportesAvanzadosController::class, 'carteraClientes'])->name('cartera_clientes');
            Route::get('cartera-clientes/pdf', [App\Http\Controllers\ReportesAvanzadosController::class, 'carteraClientesPdf'])->name('cartera_clientes.pdf');
            Route::get('cartera-clientes/excel', [App\Http\Controllers\ReportesAvanzadosController::class, 'carteraClientesExcel'])->name('cartera_clientes.excel');

            // Morosidad y Antigüedad de Saldos
            Route::get('morosidad', [App\Http\Controllers\ReportesAvanzadosController::class, 'morosidad'])->name('morosidad');
            Route::get('morosidad/pdf', [App\Http\Controllers\ReportesAvanzadosController::class, 'morosidadPdf'])->name('morosidad.pdf');
            Route::get('morosidad/excel', [App\Http\Controllers\ReportesAvanzadosController::class, 'morosidadExcel'])->name('morosidad.excel');

            // Proyección de Flujo y Recaudación
            Route::get('proyeccion-flujo', [App\Http\Controllers\ReportesAvanzadosController::class, 'proyeccionFlujo'])->name('proyeccion_flujo');
            Route::get('proyeccion-flujo/excel', [App\Http\Controllers\ReportesAvanzadosController::class, 'proyeccionFlujoExcel'])->name('proyeccion_flujo.excel');

            // Datos Legales de Clientes para Promesas de Venta
            Route::get('datos-legales-clientes', [App\Http\Controllers\ReportesAvanzadosController::class, 'datosLegalesClientes'])->name('datos_legales');
            Route::get('datos-legales-clientes/pdf', [App\Http\Controllers\ReportesAvanzadosController::class, 'datosLegalesClientesPdf'])->name('datos_legales.pdf');
            Route::get('datos-legales-clientes/excel', [App\Http\Controllers\ReportesAvanzadosController::class, 'datosLegalesClientesExcel'])->name('datos_legales.excel');
            Route::get('promesa-venta/{venta_id}/imprimir', [App\Http\Controllers\ReportesAvanzadosController::class, 'imprimirFichaLegal'])->name('promesa_venta.imprimir');
        });
    });

    // ---------------------------------------------------------------------
    // GESTIÓN Y CONFIGURACIÓN EXCLUSIVA DE ADMINISTRADOR
    // ---------------------------------------------------------------------
    Route::middleware(['role:Administrador'])->group(function () {
        // Importación Masiva de Clientes
        Route::get('importacion', [ImportacionController::class, 'index'])->name('importacion.index');
        Route::get('importacion/plantilla', [ImportacionController::class, 'descargarPlantilla'])->name('importacion.plantilla');
        Route::post('importacion/procesar', [ImportacionController::class, 'procesar'])->name('importacion.procesar');

        // Gestión del Sistema
        Route::resource('usuarios', UsuarioController::class);
        Route::get('configuracion/parametros', [ConfiguracionController::class, 'index'])->name('configuracion.parametros.index');
        Route::post('configuracion/parametros', [ConfiguracionController::class, 'update'])->name('configuracion.parametros.update');
        Route::get('auditoria', [\App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');

        // Utilidad para Hosting Compartido (BanaHosting / cPanel)
        Route::get('sistema/crear-symlink', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
                return back()->with('success', 'Enlace simbólico de almacenamiento creado exitosamente.');
            } catch (\Exception $e) {
                return back()->with('error', 'No se pudo crear el enlace simbólico: ' . $e->getMessage());
            }
        })->name('sistema.symlink');
    });

    Route::get('/errores/post', function () {
        return view('errores.post');
    });

});

/**
 * RUTA FALLBACK PARA HOSTING COMPARTIDO (BANALINK / CPANEL):
 * Si el servidor web no tiene activo el symlink 'public/storage',
 * esta ruta sirve directamente las imágenes, logos y comprobantes
 * sin romper la vista del usuario ni del portal de clientes.
 */
Route::get('storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!\Illuminate\Support\Facades\File::exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*');