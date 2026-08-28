<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lot = App\Models\Lotificacion::first();
if ($lot) {
    $admins = App\Models\User::role('Administrador')->get();
    foreach($admins as $admin) {
        $admin->lotificaciones()->syncWithoutDetaching([$lot->id]);
    }
    echo "Sincronizado con éxito.";
} else {
    echo "No hay lotificaciones.";
}
