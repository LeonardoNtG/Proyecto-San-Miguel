<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\GraficoController;
use Illuminate\Support\Facades\Route;


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
Route::get('abono/{abono_id}/imprimir', [AbonoController::class, 'imprimirRecibo'])->name('imprimirRecibo');

Route::get('/', function () {
    return view('inicio');
});
Route::get('/login', function () {
    return view('auth.login');
});
Route::get('/clientes', function () {
    return view('clientes');
});

Route::get('/dashboard-grafico', [GraficoController::class, 'dashboard'])->name('dashboard.grafico');

Route::resource('registro', App\Http\Controllers\ClienteController::class)->parameters([
    'registro' => 'cliente',]);

Route::get('/api/bloques/{bloque}/lotes', [App\Http\Controllers\LoteController::class, 'getLotesByBloque'])
    ->name('api.lotes.by.bloque');

Route::resource('reportes', App\Http\Controllers\ReporteController::class);

Route::resource('abono', App\Http\Controllers\AbonoController::class);

Route::prefix('abono/{cliente}')->name('abono.')->group(function () {
    Route::get('registrar', [App\Http\Controllers\AbonoController::class, 'create'])->name('create'); 
    
});

Route::prefix('abono/{cliente}')->name('abono.')->group(function () {
    
    Route::post('/', [App\Http\Controllers\AbonoController::class, 'store'])->name('store'); 
});
