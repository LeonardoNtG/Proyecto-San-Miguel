<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\GraficoController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Portal del Cliente (Público)
Route::get('/mi-estado/{token}', [\App\Http\Controllers\PortalClienteController::class, 'show'])->name('portal.estado_cuenta');

Route::middleware(['auth'])->group(function () {
    Route::post('/lotificacion/{id}/activa', [App\Http\Controllers\LotificacionController::class, 'setLotificacionActiva'])->name('lotificacion.setActiva');
    Route::post('/ventas/{id}/rescindir', [App\Http\Controllers\VentaController::class, 'rescindir'])->name('ventas.rescindir');

Route::get('abono/{abono_id}/imprimir', [AbonoController::class, 'imprimirRecibo'])->name('imprimirRecibo');

Route::get('/inicio', function () {
    return view('inicio');
})->name('inicio');

Route::get('/clientes', function () {
    return view('clientes');
});

Route::get('/dashboard-grafico', [GraficoController::class, 'dashboard'])->name('dashboard.grafico');

Route::resource('registro', App\Http\Controllers\ClienteController::class)->parameters([
    'registro' => 'cliente',]);

Route::resource('abonos', App\Http\Controllers\AbonoController::class);
Route::get('abonos/{abono}/imprimir', [App\Http\Controllers\AbonoController::class, 'imprimirRecibo'])->name('abonos.imprimir');

Route::post('cuotas/{cuota}/exonerar-mora', [App\Http\Controllers\CuotaController::class, 'exonerarMora'])->name('cuotas.exonerarMora');
Route::resource('reservas', App\Http\Controllers\ReservaController::class);
Route::post('reservas/{reserva}/anular', [App\Http\Controllers\ReservaController::class, 'anular'])->name('reservas.anular');
Route::get('reservas/{reserva}/formalizar', [App\Http\Controllers\ReservaController::class, 'formalizar'])->name('reservas.formalizar');
Route::post('reservas/{reserva}/formalizar', [App\Http\Controllers\ReservaController::class, 'procesarFormalizacion'])->name('reservas.procesarFormalizacion');

    Route::get('/estados-de-cuenta', [\App\Http\Controllers\ClienteController::class, 'estadosCuenta'])->name('estados_cuenta');
Route::get('/api/bloques/{bloque}/lotes', [App\Http\Controllers\LoteController::class, 'getLotesByBloque'])
    ->name('api.lotes.by.bloque');

Route::get('/api/lotificaciones/{lotificacion}/bloques', [App\Http\Controllers\BloqueController::class, 'getBloquesByLotificacion'])
    ->name('api.bloques.by.lotificacion');

// Bloques y Lotes: gestión (alta/edición) restringida a administradores.
// Las rutas /api/bloques/.../lotes y /api/proyectos/.../bloques quedan fuera
// de este grupo a propósito: las necesita cualquier usuario autenticado para
// registrar clientes/ventas en "/registro/create".

// Rutas protegidas
Route::middleware(['role:Administrador'])->group(function () {
    Route::resource('bloques', App\Http\Controllers\BloqueController::class);

    Route::prefix('bloques/{bloque}/lotes')->name('lotes.')->group(function () {
        Route::get('/', [App\Http\Controllers\LoteController::class, 'index'])->name('index');
        Route::get('crear', [App\Http\Controllers\LoteController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\LoteController::class, 'store'])->name('store');
    });

    Route::prefix('lotes')->name('lotes.')->group(function () {
        Route::get('{lote}/editar', [App\Http\Controllers\LoteController::class, 'edit'])->name('edit');
        Route::put('{lote}', [App\Http\Controllers\LoteController::class, 'update'])->name('update');
        Route::delete('{lote}', [App\Http\Controllers\LoteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('financiero', [App\Http\Controllers\ReporteController::class, 'financiero'])->name('financiero');
        Route::get('financiero/pdf', [App\Http\Controllers\ReporteController::class, 'financieroPdf'])->name('financiero.pdf');
        Route::get('financiero/excel', [App\Http\Controllers\ReporteController::class, 'financieroExcel'])->name('financiero.excel');
        Route::post('cerrar-caja', [App\Http\Controllers\ReporteController::class, 'cerrarCaja'])->name('cerrarCaja');
        Route::get('cierre-caja', [App\Http\Controllers\ReportesController::class, 'cierreCaja'])->name('cierre_caja');
    });

    Route::resource('reportes', App\Http\Controllers\ReporteController::class);
});



Route::resource('abono', App\Http\Controllers\AbonoController::class);

Route::prefix('abono/{cliente}')->name('abono.')->group(function () {
    Route::get('registrar', [App\Http\Controllers\AbonoController::class, 'create'])->name('create'); 
    
});

Route::prefix('abono/{cliente}')->name('abono.')->group(function () {
    
    Route::post('/', [App\Http\Controllers\AbonoController::class, 'store'])->name('store'); 
});

Route::middleware(['auth', 'role:Administrador'])->group(function () {

    Route::resource('usuarios', UsuarioController::class);
    Route::resource('lotificaciones', App\Http\Controllers\LotificacionController::class);
    
    Route::get('auditoria', [App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');

});

Route::get('/errores/post', function () {
    return view('errores.post');
});

});