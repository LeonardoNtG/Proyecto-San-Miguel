<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener el primer cliente y su primera venta
$cliente = \App\Models\Cliente::first();
$venta = $cliente->ventas->first();

if ($venta) {
    if ($venta->cuotas->count() === 0) {
        echo "Generando cuotas para la venta {$venta->id_venta}...\n";
        // Generar 60 cuotas
        $fechaVencimiento = \Carbon\Carbon::parse($venta->created_at);
        $saldoRestante = $venta->precio_final;
        $cuotaMensual = $venta->cuota_mensual;

        for ($i = 1; $i <= $venta->plazo_meses; $i++) {
            $fechaVencimiento->addMonth();
            $montoCuota = ($i == $venta->plazo_meses) ? $saldoRestante : $cuotaMensual;
            
            \App\Models\Cuota::create([
                'id_venta' => $venta->id_venta,
                'numero_cuota' => $i,
                'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                'monto_total' => $montoCuota,
                'capital' => $montoCuota,
                'interes' => 0,
                'saldo_restante' => $montoCuota,
                'estado' => 'Pendiente',
            ]);
            $saldoRestante -= $montoCuota;
        }
        
        // Poner la cuota 1 en mora
        $cuota = \App\Models\Cuota::where('id_venta', $venta->id_venta)->where('numero_cuota', 1)->first();
        $cuota->fecha_vencimiento = now()->subMonths(2)->subDays(10)->format('Y-m-d');
        $cuota->save();
        
        \Illuminate\Support\Facades\Artisan::call('mora:calcular');
        echo "Se generaron las cuotas y se aplicó la mora a la primera.\n";
    } else {
        echo "La venta ya tiene cuotas. Asegurando que la primera esté en mora...\n";
        $cuota = $venta->cuotas->first();
        $cuota->estado = 'Pendiente';
        $cuota->saldo_restante = $cuota->monto_total;
        $cuota->fecha_vencimiento = now()->subMonths(2)->subDays(10)->format('Y-m-d');
        $cuota->save();
        \Illuminate\Support\Facades\Artisan::call('mora:calcular');
        echo "Mora aplicada.\n";
    }
}
