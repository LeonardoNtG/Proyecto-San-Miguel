<?php
/**
 * ASISTENTE DE INSTALACIÓN Y DESPLIEGUE AUTOMATIZADO
 * Sistema de Control de Lotificaciones
 * Diseñado especialmente para servidores cPanel / BanaHosting
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

$baseDir = __DIR__;
if (!file_exists($baseDir . '/artisan') && file_exists(dirname($baseDir) . '/artisan')) {
    $baseDir = dirname($baseDir);
}

$lockFile = $baseDir . '/storage/installed.lock';
$envFile  = $baseDir . '/.env';

// Auto-eliminación del instalador a solicitud
if (isset($_GET['action']) && $_GET['action'] === 'eliminar') {
    $f1 = __DIR__ . '/instalar.php';
    $f2 = $baseDir . '/public/instalar.php';
    if (file_exists($f1)) @unlink($f1);
    if (file_exists($f2)) @unlink($f2);
    header('Location: ./login');
    exit;
}

$isInstalled = file_exists($lockFile);

// Autodetección de URL base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
$scriptDir = preg_replace('#/public$#', '', $scriptDir);
$autoAppUrl = rtrim($protocol . $host . $scriptDir, '/');

// Verificación de Requisitos
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0.2', '>=');

$requiredExtensions = [
    'pdo_mysql' => 'PDO MySQL (Conexión a Base de Datos)',
    'mbstring'  => 'Mbstring (Manejo de cadenas UTF-8)',
    'fileinfo'  => 'Fileinfo (Detección de tipos de archivo)',
    'gd'        => 'GD (Generación y manipulación de imágenes/logos)',
    'zip'       => 'Zip (Requerido para Importación Masiva Excel)',
    'xml'       => 'XML / SimpleXML (Procesamiento de documentos)',
    'bcmath'    => 'BCMath (Cálculos matemáticos de precisión)',
    'curl'      => 'cURL (Peticiones HTTP seguras)',
    'json'      => 'JSON (Formato de datos)',
    'ctype'     => 'Ctype (Validación de tipos de caracteres)',
    'tokenizer' => 'Tokenizer (Compilación de plantillas Blade)',
];

$extResults = [];
$allExtsOk = true;
foreach ($requiredExtensions as $ext => $label) {
    $loaded = extension_loaded($ext);
    if (!$loaded && $ext === 'gd' && extension_loaded('imagick')) {
        $loaded = true; // Fallback con imagick
    }
    $extResults[$ext] = ['label' => $label, 'ok' => $loaded];
    if (!$loaded) $allExtsOk = false;
}

// Verificación y auto-corrección de permisos
$writableDirs = [
    'storage'                     => $baseDir . '/storage',
    'storage/app'                 => $baseDir . '/storage/app',
    'storage/app/public'          => $baseDir . '/storage/app/public',
    'storage/framework'           => $baseDir . '/storage/framework',
    'storage/framework/cache'     => $baseDir . '/storage/framework/cache',
    'storage/framework/sessions'  => $baseDir . '/storage/framework/sessions',
    'storage/framework/views'     => $baseDir . '/storage/framework/views',
    'storage/logs'                => $baseDir . '/storage/logs',
    'bootstrap/cache'             => $baseDir . '/bootstrap/cache',
];

$permResults = [];
$allPermsOk = true;
foreach ($writableDirs as $name => $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    @chmod($path, 0775);
    $isWritable = is_writable($path);
    $permResults[$name] = ['path' => $path, 'ok' => $isWritable];
    if (!$isWritable) $allPermsOk = false;
}

// Procesamiento de Instalación (POST)
$mensajeExito = '';
$mensajeError = '';
$detallesLogs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ejecutar_instalacion']) && !$isInstalled) {
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $appUrl = rtrim(trim($_POST['app_url'] ?? $autoAppUrl), '/');
    $appName = trim($_POST['app_name'] ?? 'Sistema San Miguel');

    // 1. Probar Conexión PDO
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $detallesLogs[] = "✔ Conexión con la base de datos '{$dbName}' exitosa.";
    } catch (PDOException $e) {
        $mensajeError = "No se pudo conectar a la base de datos MySQL: " . $e->getMessage() . ". Por favor verifica los datos ingresados.";
    }

    // 2. Si la conexión es exitosa, escribir .env
    if (empty($mensajeError)) {
        try {
            $currentAppKey = '';
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $m)) {
                    $currentAppKey = trim($m[1]);
                }
            }
            if (empty($currentAppKey)) {
                $currentAppKey = 'base64:' . base64_encode(random_bytes(32));
            }

            $newEnv = "APP_NAME=\"" . addslashes($appName) . "\"\n" .
                      "APP_ENV=production\n" .
                      "APP_KEY={$currentAppKey}\n" .
                      "APP_DEBUG=false\n" .
                      "APP_URL={$appUrl}\n\n" .
                      "LOG_CHANNEL=stack\n" .
                      "LOG_DEPRECATIONS_CHANNEL=null\n" .
                      "LOG_LEVEL=error\n\n" .
                      "DB_CONNECTION=mysql\n" .
                      "DB_HOST={$dbHost}\n" .
                      "DB_PORT={$dbPort}\n" .
                      "DB_DATABASE={$dbName}\n" .
                      "DB_USERNAME={$dbUser}\n" .
                      "DB_PASSWORD=\"{$dbPass}\"\n\n" .
                      "BROADCAST_DRIVER=log\n" .
                      "CACHE_DRIVER=file\n" .
                      "FILESYSTEM_DISK=public\n" .
                      "QUEUE_CONNECTION=sync\n" .
                      "SESSION_DRIVER=file\n" .
                      "SESSION_LIFETIME=120\n" .
                      "SESSION_SECURE_COOKIE=true\n\n" .
                      "MEMCACHED_HOST=127.0.0.1\n" .
                      "REDIS_HOST=127.0.0.1\n" .
                      "REDIS_PASSWORD=null\n" .
                      "REDIS_PORT=6379\n\n" .
                      "MAIL_MAILER=smtp\n" .
                      "MAIL_HOST=mailpit\n" .
                      "MAIL_PORT=1025\n" .
                      "MAIL_USERNAME=null\n" .
                      "MAIL_PASSWORD=null\n" .
                      "MAIL_ENCRYPTION=null\n" .
                      "MAIL_FROM_ADDRESS=\"notificaciones@sanmiguel.com\"\n" .
                      "MAIL_FROM_NAME=\"\${APP_NAME}\"\n";

            file_put_contents($envFile, $newEnv);
            $detallesLogs[] = "✔ Archivo de configuración .env generado en modo producción.";

            // 3. Cargar Laravel para ejecutar migraciones y optimizaciones
            require_once $baseDir . '/vendor/autoload.php';
            $app = require_once $baseDir . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            // Limpiar cachés previas
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $detallesLogs[] = "✔ Caché del sistema limpia.";

            // Ejecutar migraciones
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $detallesLogs[] = "✔ Migraciones de base de datos ejecutadas correctamente.";

            // Ejecutar seeders iniciales si la tabla roles o usuarios está vacía
            $userCount = \Illuminate\Support\Facades\DB::table('users')->count();
            if ($userCount === 0) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                    $detallesLogs[] = "✔ Datos iniciales y roles sembrados (db:seed) con éxito.";
                } catch (\Exception $seederEx) {
                    $detallesLogs[] = "⚠ Aviso en seeders: " . $seederEx->getMessage();
                }
            } else {
                $detallesLogs[] = "✔ Base de datos ya contenía {$userCount} usuario(s) registrado(s).";
            }

            // Crear enlace simbólico de almacenamiento
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
                $detallesLogs[] = "✔ Enlace simbólico de almacenamiento creado (storage:link).";
            } catch (\Exception $linkEx) {
                // Fallback manual con symlink nativo
                $target = $baseDir . '/storage/app/public';
                $link = $baseDir . '/public/storage';
                if (!file_exists($link) && function_exists('symlink')) {
                    @symlink($target, $link);
                }
                $detallesLogs[] = "✔ Enlace de almacenamiento configurado vía fallback.";
            }

            // Optimizar para producción
            \Illuminate\Support\Facades\Artisan::call('config:cache');
            \Illuminate\Support\Facades\Artisan::call('route:cache');
            \Illuminate\Support\Facades\Artisan::call('view:cache');
            $detallesLogs[] = "✔ Caché de rutas, vistas y configuración compiladas para máxima velocidad.";

            // Crear archivo de bloqueo
            file_put_contents($lockFile, "Instalado exitosamente el: " . date('Y-m-d H:i:s') . "\nURL: {$appUrl}\n");
            $isInstalled = true;
            $mensajeExito = "¡El sistema ha sido instalado y configurado con éxito!";

        } catch (\Exception $e) {
            $mensajeError = "Ocurrió un error durante la ejecución de Laravel: " . $e->getMessage();
            $detallesLogs[] = "❌ Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Automático &middot; Sistema de Lotificaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4338ca;
            --primary-hover: #3730a3;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-bg); color: var(--text-main); min-height: 100vh; padding: 40px 20px; display: flex; justify-content: center; align-items: flex-start; }
        .installer-card { background: var(--card-bg); width: 100%; max-width: 850px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); border: 1px solid var(--border); overflow: hidden; }
        .installer-header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #fff; padding: 32px; text-align: center; }
        .installer-header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .installer-header p { font-size: 14px; opacity: 0.85; }
        .installer-body { padding: 32px; }
        .section-title { font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .check-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f1f5f9; border-radius: 8px; font-size: 13px; border: 1px solid var(--border); }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
        .form-control { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); }
        .alert { padding: 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; gap: 12px; align-items: flex-start; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: #fff; width: 100%; font-size: 15px; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .log-box { background: #0f172a; color: #38bdf8; font-family: monospace; font-size: 12px; padding: 16px; border-radius: 8px; max-height: 220px; overflow-y: auto; margin-top: 16px; }
        .log-line { margin-bottom: 4px; }
        hr { border: 0; height: 1px; background: var(--border); margin: 24px 0; }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="installer-header">
        <i class="fas fa-rocket fa-3x" style="margin-bottom: 12px; color: #818cf8;"></i>
        <h1>Instalador y Despliegue en 1 Clic</h1>
        <p>Configuración rápida para Servidores cPanel / BanaHosting &middot; Laravel Framework</p>
    </div>

    <div class="installer-body">
        <?php if ($isInstalled): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <h3 style="font-weight: 700; margin-bottom: 4px;">¡El sistema ya se encuentra instalado y listo para usar!</h3>
                    <p>Todas las tablas, configuraciones y cachés han sido inicializadas correctamente.</p>
                </div>
            </div>

            <?php if (!empty($detallesLogs)): ?>
                <div class="section-title"><i class="fas fa-terminal"></i> Resumen de Acciones Ejecutadas:</div>
                <div class="log-box">
                    <?php foreach ($detallesLogs as $log): ?>
                        <div class="log-line"><?= htmlspecialchars($log) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="./login" class="btn btn-success" style="flex: 1;">
                    <i class="fas fa-sign-in-alt"></i> Ir al Inicio de Sesión
                </a>
                <a href="?action=eliminar" class="btn btn-danger" onclick="return confirm('¿Deseas eliminar este instalador por seguridad e ingresar al sistema?');">
                    <i class="fas fa-trash-alt"></i> Eliminar Instalador e Ingresar
                </a>
            </div>

        <?php else: ?>

            <?php if (!empty($mensajeError)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                    <div><?= htmlspecialchars($mensajeError) ?></div>
                </div>
            <?php endif; ?>

            <!-- PASO 1: DIAGNÓSTICO -->
            <div class="section-title"><i class="fas fa-microchip text-primary"></i> 1. Verificación del Servidor PHP</div>
            <div class="grid-3" style="margin-bottom: 20px;">
                <div class="check-item">
                    <span>Versión PHP (>= 8.0.2)</span>
                    <span class="badge <?= $phpOk ? 'badge-ok' : 'badge-fail' ?>">PHP <?= $phpVersion ?></span>
                </div>
                <?php foreach ($extResults as $ext => $data): ?>
                    <div class="check-item">
                        <span><?= $ext ?></span>
                        <span class="badge <?= $data['ok'] ? 'badge-ok' : 'badge-fail' ?>"><?= $data['ok'] ? 'Activo' : 'Inactivo' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title"><i class="fas fa-folder-open text-primary"></i> 2. Permisos de Escritura de Carpetas</div>
            <div class="grid-3" style="margin-bottom: 24px;">
                <?php foreach ($permResults as $dir => $data): ?>
                    <div class="check-item">
                        <span><?= $dir ?></span>
                        <span class="badge <?= $data['ok'] ? 'badge-ok' : 'badge-fail' ?>"><?= $data['ok'] ? 'Correcto (775)' : 'Sin Escritura' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$phpOk || !$allExtsOk || !$allPermsOk): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-info-circle fa-lg"></i>
                    <div>
                        <strong>Atención:</strong> Se detectaron componentes pendientes. En cPanel ve a <em>"Select PHP Version"</em> y activa las extensiones faltantes o ajusta los permisos de las carpetas a 775.
                    </div>
                </div>
            <?php endif; ?>

            <hr>

            <!-- PASO 2: FORMULARIO -->
            <form method="POST" action="">
                <div class="section-title"><i class="fas fa-database text-primary"></i> 3. Datos de la Base de Datos (MySQL)</div>
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i>
                    <div>Ingresa el nombre y credenciales de la base de datos que creaste en tu cPanel de BanaHosting.</div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombre del Sistema:</label>
                        <input type="text" name="app_name" class="form-control" value="Sistema San Miguel" required>
                    </div>
                    <div class="form-group">
                        <label>URL de la Aplicación:</label>
                        <input type="text" name="app_url" class="form-control" value="<?= htmlspecialchars($autoAppUrl) ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Servidor MySQL (Host):</label>
                        <input type="text" name="db_host" class="form-control" value="127.0.0.1" required>
                    </div>
                    <div class="form-group">
                        <label>Puerto MySQL:</label>
                        <input type="text" name="db_port" class="form-control" value="3306" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombre de Base de Datos:</label>
                        <input type="text" name="db_name" class="form-control" placeholder="ej: usuario_lotificacion" required>
                    </div>
                    <div class="form-group">
                        <label>Usuario de Base de Datos:</label>
                        <input type="text" name="db_user" class="form-control" placeholder="ej: usuario_admin" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Contraseña de Base de Datos:</label>
                    <input type="password" name="db_pass" class="form-control" placeholder="Contraseña creada en cPanel">
                </div>

                <button type="submit" name="ejecutar_instalacion" value="1" class="btn btn-primary" style="margin-top: 12px;" <?= (!$phpOk || !$allExtsOk) ? 'disabled' : '' ?>>
                    <i class="fas fa-bolt"></i> Instalar Sistema Automáticamente
                </button>
            </form>

        <?php endif; ?>
    </div>
</div>

</body>
</html>
