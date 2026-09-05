<?php
/**
 * Script de Diagnóstico, Migración y Reparación para BanaHosting / Producción
 * Acceso directo vía navegador: https://proyectosanmiguel.com/AMSAsystem/limpiar.php
 */

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

        // 4. Asegurar columnas de compatibilidad (por si hay archivos antiguos en servidor)
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
        }

        // 5. Limpiar caché desde Artisan
        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Ignorar si falla
        }

        // 6. Verificar tablas críticas
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

// 7. Leer los últimos errores con detalle de storage/logs/laravel.log
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
            <a href="./abonos/11/imprimir" class="btn" style="background: #7c3aed;">🖨️ Ver Recibo 11</a>
        </div>

        <?php
            $abonoCtrlFile = $baseDir . '/app/Http/Controllers/AbonoController.php';
            $abonoCtrlDate = file_exists($abonoCtrlFile) ? date("Y-m-d H:i:s", filemtime($abonoCtrlFile)) : "NO EXISTE";
            $pruebaLetras = "N/A";
            try {
                if (class_exists(\App\Http\Controllers\AbonoController::class)) {
                    $ac = new \App\Http\Controllers\AbonoController();
                    $ref = new \ReflectionMethod($ac, 'convertirMontoALetras');
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
        </div>    <a href="./limpiar.php" class="btn" style="background: #475569;">🔄 Reejecutar Diagnóstico</a>
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
