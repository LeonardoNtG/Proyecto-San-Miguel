<?php
/**
 * Script de Diagnóstico, Migración y Reparación para BanaHosting / Producción
 * Acceso directo vía navegador: https://proyectosanmiguel.com/AMSAsystem/limpiar.php
 */

@set_time_limit(180);
@ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$baseDir = __DIR__;
if (!file_exists($baseDir . '/artisan') && file_exists(dirname($baseDir) . '/artisan')) {
    $baseDir = dirname($baseDir);
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
}
clearstatcache(true);

// 1. Borrar vistas compiladas en caché
$viewsDir = $baseDir . '/storage/framework/views';
$borradosVistas = 0;
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') as $file) {
        if (is_file($file)) { @unlink($file); $borradosVistas++; }
    }
}
clearstatcache(true);

// 2. Borrar solo caché de configuración y rutas (NUNCA borrar packages.php)
$cacheDir = $baseDir . '/bootstrap/cache';
$borradosCache = 0;
if (is_dir($cacheDir)) {
    foreach (['routes-v7.php', 'config.php', 'events.php'] as $f) {
        $filePath = $cacheDir . '/' . $f;
        if (is_file($filePath)) { @unlink($filePath); $borradosCache++; }
    }
}
clearstatcache(true);

$migrationOutput = "";
$logEntries = [];
$dbStatus = "";
$tablesChecked = [];
$columnFixes = [];

try {
    if (file_exists($baseDir . '/vendor/autoload.php') && file_exists($baseDir . '/bootstrap/app.php')) {
        require_once $baseDir . '/vendor/autoload.php';
        $app = require_once $baseDir . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        // 3. Ejecutar Migraciones pendientes automáticamente
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = \Illuminate\Support\Facades\Artisan::output();
        } catch (\Throwable $e) {
            $migrationOutput = "Aviso al migrar: " . $e->getMessage();
        }

        // 4. Asegurar columnas y tablas de compatibilidad
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('historial_lotes')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('historial_lotes', 'motivo_liberacion')) {
                    \Illuminate\Support\Facades\Schema::table('historial_lotes', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('motivo_liberacion')->nullable()->after('observaciones');
                    });
                    $columnFixes[] = "✔ Agregada columna de compatibilidad 'motivo_liberacion' a la tabla 'historial_lotes'.";
                }
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('abonos')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'comentario')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('comentario')->nullable()->after('cuenta_destino');
                    });
                    $columnFixes[] = "✔ Agregada columna 'comentario' a la tabla 'abonos'.";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'fecha_transferencia')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->date('fecha_transferencia')->nullable()->after('cuenta_destino');
                    });
                    $columnFixes[] = "✔ Agregada columna 'fecha_transferencia' a la tabla 'abonos'.";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'recibo_firmado')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('recibo_firmado')->nullable()->after('ruta_recibo');
                    });
                    $columnFixes[] = "✔ Agregada columna 'recibo_firmado' a la tabla 'abonos'.";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'fecha_recibo_firmado')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->dateTime('fecha_recibo_firmado')->nullable()->after('recibo_firmado');
                    });
                    $columnFixes[] = "✔ Agregada columna 'fecha_recibo_firmado' a la tabla 'abonos'.";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'user_recibo_firmado_id')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->unsignedBigInteger('user_recibo_firmado_id')->nullable()->after('fecha_recibo_firmado');
                    });
                    $columnFixes[] = "✔ Agregada columna 'user_recibo_firmado_id' a la tabla 'abonos'.";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('abonos', 'es_migracion')) {
                    \Illuminate\Support\Facades\Schema::table('abonos', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->boolean('es_migracion')->default(false)->after('comentario');
                    });
                    $columnFixes[] = "✔ Agregada columna 'es_migracion' a la tabla 'abonos'.";
                }
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('configuraciones')) {
                \Illuminate\Support\Facades\Schema::create('configuraciones', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('lotificacion_id')->index();
                    $table->string('clave', 100);
                    $table->text('valor')->nullable();
                    $table->string('tipo', 30)->default('string');
                    $table->string('grupo', 50)->default('general');
                    $table->string('descripcion', 255)->nullable();
                    $table->timestamps();

                    $table->unique(['lotificacion_id', 'clave']);
                });
                $columnFixes[] = "✔ Tabla 'configuraciones' creada exitosamente en la base de datos.";
            }
        } catch (\Throwable $e) {
            $columnFixes[] = "Aviso en verificación de columnas: " . $e->getMessage();
        }

        // 5. Limpiar caché desde Artisan
        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Ignorar si falla
        }

        // 5.1 Ejecución bajo demanda de recálculo masivo de contratos
        if (isset($_GET['recalcular_todo']) && $_GET['recalcular_todo'] === '1') {
            try {
                $todasVentas = \App\Models\Venta::withoutGlobalScope('lotificacion')->get();
                foreach ($todasVentas as $v) {
                    \App\Http\Controllers\AbonoController::recalcularCuotas($v->id_venta);
                }
                $columnFixes[] = "✔ Cuotas recalculadas y sincronizadas exitosamente para " . $todasVentas->count() . " contratos.";
            } catch (\Throwable $e) {
                $columnFixes[] = "⚠ Error recalculando contratos: " . $e->getMessage();
            }
        }

        // 5.2 Limpieza bajo demanda de Clientes de La Campana
        if (isset($_GET['limpiar_campana']) && $_GET['limpiar_campana'] === '1') {
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();
                $campana = \App\Models\Lotificacion::where('nombre', 'like', '%Campana%')->orWhere('id', 1)->first();
                if ($campana) {
                    $ventasCampana = \App\Models\Venta::withoutGlobalScope('lotificacion')->where('lotificacion_id', $campana->id)->get();
                    $ventaIds = $ventasCampana->pluck('id_venta')->toArray();
                    $clienteIdsCampana = $ventasCampana->pluck('id_cliente')->unique()->toArray();

                    $rescisionesEliminadas = \App\Models\Rescision::withoutGlobalScope('lotificacion')
                        ->where('lotificacion_id', $campana->id)
                        ->orWhereIn('id_venta', $ventaIds)
                        ->delete();

                    $abonosEliminados = \App\Models\Abono::withoutGlobalScope('lotificacion')
                        ->whereIn('id_venta', $ventaIds)
                        ->delete();

                    $cuotasEliminadas = \App\Models\Cuota::withoutGlobalScope('lotificacion')
                        ->whereIn('id_venta', $ventaIds)
                        ->delete();

                    $bloquesCampana = \App\Models\Bloque::withoutGlobalScope('lotificacion')->where('lotificacion_id', $campana->id)->pluck('id_bloque')->toArray();
                    $lotesCampana = \App\Models\Lote::withoutGlobalScope('lotificacion')->whereIn('id_bloque', $bloquesCampana)->pluck('id_lote')->toArray();

                    $historialEliminado = \App\Models\HistorialLote::whereIn('id_venta', $ventaIds)
                        ->orWhereIn('id_lote', $lotesCampana)
                        ->delete();

                    $reservasEliminadas = \App\Models\Reserva::withoutGlobalScope('lotificacion')
                        ->where('lotificacion_id', $campana->id)
                        ->delete();

                    $ventasEliminadas = \App\Models\Venta::withoutGlobalScope('lotificacion')
                        ->where('lotificacion_id', $campana->id)
                        ->delete();

                    // Preservar inventario y resetear lotes
                    $lotesActualizados = \App\Models\Lote::withoutGlobalScope('lotificacion')
                        ->whereIn('id_bloque', $bloquesCampana)
                        ->update(['estado' => 'Disponible']);

                    $clientesEliminadosCount = 0;
                    foreach ($clienteIdsCampana as $cId) {
                        $tieneOtrasVentas = \App\Models\Venta::withoutGlobalScope('lotificacion')
                            ->where('id_cliente', $cId)
                            ->exists();
                        if (!$tieneOtrasVentas) {
                            \App\Models\Cliente::withoutGlobalScope('lotificacion')->where('id_cliente', $cId)->delete();
                            $clientesEliminadosCount++;
                        }
                    }

                    \Illuminate\Support\Facades\DB::commit();

                    $campanaCleanReport = "✔ Limpieza de La Campana ejecutada exitosamente: {$ventasEliminadas} ventas, {$abonosEliminados} abonos, {$cuotasEliminadas} cuotas, {$clientesEliminadosCount} clientes eliminados. {$lotesActualizados} lotes preservados en inventario y reseteados a 'Disponible'. Otros proyectos 100% intactos.";
                    $columnFixes[] = $campanaCleanReport;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $columnFixes[] = "❌ Error en limpieza de La Campana: " . $e->getMessage();
            }
        }

        // 6. Auditoría detallada de inventario de lotes
        $auditoriaLotes = [];
        try {
            $lotificacionesList = \App\Models\Lotificacion::all();
            foreach ($lotificacionesList as $lotif) {
                $bloques = \App\Models\Bloque::withoutGlobalScope('lotificacion')
                    ->where('lotificacion_id', $lotif->id)
                    ->with(['lotes' => function($q) {
                        $q->withoutGlobalScope('lotificacion')->orderBy('id_lote');
                    }])
                    ->orderBy('nombre')
                    ->get();

                $totalLotesLotif = 0;
                $detallesBloques = [];
                $duplicados = [];
                $lotesEspeciales = [];

                foreach ($bloques as $b) {
                    $cant = $b->lotes->count();
                    $totalLotesLotif += $cant;
                    
                    $lotesNums = $b->lotes->pluck('numero_lote')->toArray();
                    $conteoNums = array_count_values(array_map('strval', $lotesNums));
                    foreach ($conteoNums as $num => $rep) {
                        if ($rep > 1) {
                            $duplicados[] = "Bloque {$b->nombre} tiene el Lote {$num} repetido {$rep} veces";
                        }
                    }

                    // Chequear si hay lotes no numéricos o especiales
                    foreach ($b->lotes as $lt) {
                        if (!is_numeric($lt->numero_lote) || (int)$lt->numero_lote === 0) {
                            $lotesEspeciales[] = "Bloque {$b->nombre} - Lote: '{$lt->numero_lote}' (ID: {$lt->id_lote})";
                        }
                    }

                    $prim = !empty($lotesNums) ? implode(', ', array_slice($lotesNums, 0, 3)) : 'Sin lotes';
                    $ult = !empty($lotesNums) ? end($lotesNums) : '';
                    $rango = !empty($ult) ? "({$prim} ... {$ult})" : "({$prim})";

                    $detallesBloques[] = "Bloque {$b->nombre}: <strong>{$cant} lotes</strong> {$rango}";
                }

                $auditoriaLotes[] = [
                    'proyecto' => $lotif->nombre,
                    'proyecto_id' => $lotif->id,
                    'total_lotes' => $totalLotesLotif,
                    'bloques' => $detallesBloques,
                    'duplicados' => $duplicados,
                    'lotes_especiales' => $lotesEspeciales
                ];
            }
        } catch (\Throwable $e) {
            $auditoriaLotes = [
                ['proyecto' => 'Error al auditar', 'total_lotes' => 0, 'bloques' => [$e->getMessage()], 'duplicados' => [], 'lotes_especiales' => []]
            ];
        }

        // 7. Verificar tablas críticas
        $tablesToVerify = ['users', 'lotificaciones', 'lotificacion_user', 'clientes', 'ventas', 'cuotas', 'abonos', 'historial_lotes', 'apertura_cajas', 'cierre_cajas', 'salidas', 'configuraciones', 'rescisiones', 'cuentas_bancarias'];
        foreach ($tablesToVerify as $t) {
            try {
                $exists = \Illuminate\Support\Facades\Schema::hasTable($t);
                $tablesChecked[$t] = $exists;
            } catch (\Throwable $e) {
                $tablesChecked[$t] = false;
            }
        }

        $dbStatus = "✔ Conexión a Base de Datos exitosa y migraciones ejecutadas.";
    } else {
        $dbStatus = "⚠ No se encontró el autoload de Laravel en: " . htmlspecialchars($baseDir);
    }
} catch (\Throwable $e) {
    $dbStatus = "❌ Error en el arranque de Laravel: " . $e->getMessage() . " (" . $e->getFile() . " L#" . $e->getLine() . ")";
}

// 8. Leer los últimos errores con detalle de storage/logs/laravel.log
$logFile = $baseDir . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*?(?=(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])|$)/s', $content, $matches);
    if (!empty($matches[0])) {
        $logEntries = array_slice($matches[0], -5);
    } else {
        $lines = file($logFile);
        $logEntries = [implode("", array_slice($lines, -60))];
    }
} else {
    $logEntries = ["No se encontró el archivo storage/logs/laravel.log"];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico y Reparación - Sistema AMSA</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; margin: 0; }
        .container { max-width: 1050px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 1.6rem; }
        h2 { color: #94a3b8; font-size: 1.1rem; margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 8px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; margin-right: 6px; margin-bottom: 6px; }
        .badge-success { background: #065f46; color: #6ee7b7; border: 1px solid #059669; }
        .badge-danger { background: #7f1d1d; color: #fca5a5; border: 1px solid #dc2626; }
        pre { background: #020617; color: #e2e8f0; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; border: 1px solid #1e293b; white-space: pre-wrap; word-wrap: break-word; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; margin-right: 8px; margin-bottom: 8px; transition: 0.2s; }
        .btn:hover { background: #1d4ed8; }
        .msg-box { padding: 14px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; font-size: 1rem; }
        .msg-ok { background: #064e3b; color: #a7f3d0; border: 1px solid #047857; }
        .msg-err { background: #450a0a; color: #fecaca; border: 1px solid #b91c1c; }
        .error-card { background: #2a1215; border: 1px solid #842029; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .error-card h3 { color: #f87171; margin-top: 0; font-size: 0.95rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>🩺 Panel de Diagnóstico y Reparación en Servidor</h1>
        <p>Caché limpiada: <strong><?= $borradosVistas ?></strong> vistas y <strong><?= $borradosCache ?></strong> archivos de configuración.</p>
        
        <div class="msg-box <?= str_contains($dbStatus, '❌') ? 'msg-err' : 'msg-ok' ?>">
            <?= htmlspecialchars($dbStatus) ?>
        </div>

        <?php if(!empty($columnFixes)): ?>
            <div class="msg-box msg-ok">
                <?= implode("<br>", $columnFixes) ?>
            </div>
        <?php endif; ?>

        <div>
            <a href="./inicio" class="btn">🚀 Probar Inicio (/inicio)</a>
            <a href="./registro" class="btn" style="background: #059669;">👥 Probar Clientes (/registro)</a>
            <a href="./limpiar.php?recalcular_todo=1" class="btn" style="background: #7c3aed;">🔄 Recalcular Todo</a>
            <a href="./limpiar.php" class="btn" style="background: #475569;">🔄 Reejecutar Diagnóstico</a>
        </div>

        <?php
            $abonoCtrlFile = $baseDir . '/app/Http/Controllers/AbonoController.php';
            $abonoCtrlDate = file_exists($abonoCtrlFile) ? date("Y-m-d H:i:s", filemtime($abonoCtrlFile)) : "NO EXISTE";
            $pruebaLetras = "N/A";
            try {
                if (class_exists(\App\Http\Controllers\AbonoController::class)) {
                    $ac = new \App\Http\Controllers\AbonoController();
                    $ref = new \ReflectionMethod($ac, 'numeroALetras');
                    $ref->setAccessible(true);
                    $pruebaLetras = $ref->invoke($ac, 150.00);
                }
            } catch (\Throwable $e) {
                $pruebaLetras = "Error probando: " . $e->getMessage();
            }
        ?>
        <div style="margin-top: 15px; padding: 12px; background: #0f172a; border-radius: 8px; border: 1px solid #3b82f6;">
            <p style="margin: 0 0 5px 0; color: #38bdf8;"><strong>🔍 Verificación de AbonoController en Servidor:</strong></p>
            <p style="margin: 0; font-size: 0.9rem;">Ruta base: <code><?= htmlspecialchars($baseDir) ?></code></p>
            <p style="margin: 0; font-size: 0.9rem;">Última modificación de AbonoController.php: <strong><?= $abonoCtrlDate ?></strong></p>
            <p style="margin: 0; font-size: 0.9rem;">Resultado de convertir 150.00: <strong style="color: #4ade80;">"<?= htmlspecialchars($pruebaLetras) ?>"</strong></p>
        </div>
    </div>

    <div class="card">
        <h2>📊 Estado de Tablas en Base de Datos</h2>
        <div>
            <?php foreach($tablesChecked as $tabla => $existe): ?>
                <span class="badge <?= $existe ? 'badge-success' : 'badge-danger' ?>">
                    <?= $existe ? '✔ ' . $tabla : '✖ ' . $tabla . ' (FALTA)' ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <?php if(!empty($migrationOutput)): ?>
            <h2 style="margin-top: 20px;">⚙️ Salida de Migraciones (php artisan migrate)</h2>
            <pre><?= htmlspecialchars($migrationOutput) ?></pre>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>🏡 Auditoría de Inventario de Lotes por Bloque</h2>
        <?php if(!empty($auditoriaLotes)): ?>
            <?php foreach($auditoriaLotes as $audit): ?>
                <div style="background: #0f172a; padding: 15px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #334155;">
                    <h3 style="margin: 0 0 10px 0; color: #38bdf8;">
                        Proyecto: <?= htmlspecialchars($audit['proyecto']) ?> &mdash; <span style="color: #4ade80;"><?= $audit['total_lotes'] ?> Lotes en Total</span>
                    </h3>
                    
                    <?php if(!empty($audit['duplicados'])): ?>
                        <div style="background: #7f1d1d; color: #fca5a5; padding: 8px 12px; border-radius: 6px; margin-bottom: 10px; font-weight: bold;">
                            ⚠ Lotes Duplicados Detectados:<br>
                            <?= implode("<br>", $audit['duplicados']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($audit['lotes_especiales'])): ?>
                        <div style="background: #854d0e; color: #fef08a; padding: 8px 12px; border-radius: 6px; margin-bottom: 10px;">
                            ℹ Lotes Especiales / No Numéricos:<br>
                            <?= implode("<br>", $audit['lotes_especiales']) ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px; font-size: 0.85rem;">
                        <?php foreach($audit['bloques'] as $blq): ?>
                            <div style="background: #1e293b; padding: 6px 10px; border-radius: 4px; border: 1px solid #475569;">
                                <?= $blq ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📜 Últimos Errores en storage/logs/laravel.log</h2>
        <?php foreach($logEntries as $idx => $entry): ?>
            <div class="error-card">
                <h3>Error #<?= $idx + 1 ?></h3>
                <pre><?= htmlspecialchars($entry) ?></pre>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
