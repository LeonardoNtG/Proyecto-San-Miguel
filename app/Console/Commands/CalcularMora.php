<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalcularMora extends Command
{
    protected $signature = 'mora:calcular';

    protected $description = 'Calcula la mora mensual de las cuotas atrasadas';

    public function handle()
    {
        $this->info('Iniciando cálculo de mora...');

        // 1 mes + 1 día de gracia en el pasado = threshold date
        $thresholdDate = \Carbon\Carbon::now()->subMonths(1)->subDays(1)->format('Y-m-d');

        // Buscar cuotas pendientes o parciales cuya fecha de vencimiento sea <= al threshold
        $cuotasAtrasadas = \App\Models\Cuota::whereIn('estado', ['Pendiente', 'Parcial', 'Mora'])
            ->where('fecha_vencimiento', '<=', $thresholdDate)
            ->get();

        $count = 0;
        foreach ($cuotasAtrasadas as $cuota) {
            // Calcular cuántos meses COMPLETOS han pasado desde (fecha_vencimiento + 1 mes + 1 dia)
            // Una forma más simple de calcular los meses de mora acumulados:
            $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_vencimiento);
            $hoy = \Carbon\Carbon::now();
            
            // Calculamos la diferencia en meses (sin contar los días extra más allá de los meses exactos)
            // Pero como la regla es: transcurrido un mes sin pago + 1 dia.
            // Si venció el 15 Mayo, al 16 Junio es 1 mes de mora. Al 16 Julio son 2 meses, etc.
            
            $fechaInicioMora = $fechaVencimiento->copy()->addMonths(1)->addDays(1);
            
            if ($hoy->greaterThanOrEqualTo($fechaInicioMora)) {
                // Cantidad de "meses de mora"
                $mesesDeMora = 1 + $fechaInicioMora->diffInMonths($hoy);
                
                // Mora esperada = 5% * saldo_restante * mesesDeMora
                // Wait, mora is usually calculated over the quota's original amount (monto_total) 
                // or the capital? Standard is over the regular quota. We use monto_total.
                $moraCalculada = ($cuota->monto_total * 0.05) * $mesesDeMora;
                
                if ($cuota->mora_calculada != $moraCalculada) {
                    $cuota->mora_calculada = $moraCalculada;
                    if ($cuota->estado != 'Mora' && $cuota->saldo_restante > 0) {
                        $cuota->estado = 'Mora';
                    }
                    $cuota->save();
                    $count++;
                }
            }
        }

        $this->info("Proceso finalizado. $count cuotas actualizadas.");
        return 0;
    }
}
