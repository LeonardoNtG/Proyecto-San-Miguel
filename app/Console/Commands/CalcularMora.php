<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalcularMora extends Command
{
    protected $signature = 'mora:calcular';

    protected $description = 'Calcula la mora mensual de las cuotas atrasadas';

    public function handle()
    {
        $this->info('Iniciando cálculo de mora según las reglas de cada proyecto...');

        // Buscar todas las cuotas pendientes o en mora cuya fecha de vencimiento sea anterior a hoy
        $cuotasAtrasadas = \App\Models\Cuota::with('venta')
            ->whereIn('estado', ['Pendiente', 'Parcial', 'Mora'])
            ->whereDate('fecha_vencimiento', '<', now()->format('Y-m-d'))
            ->get();

        $count = 0;
        foreach ($cuotasAtrasadas as $cuota) {
            $moraAnterior = $cuota->mora_calculada;
            $moraNueva = $cuota->recalcularMoraSegunConfiguracion();

            if ($moraAnterior != $moraNueva) {
                $count++;
            }
        }

        $this->info("Proceso finalizado. {$count} cuotas actualizadas con mora proporcional diaria.");
        return 0;
    }
}
