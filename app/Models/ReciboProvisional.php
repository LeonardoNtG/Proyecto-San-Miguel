<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReciboProvisional extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;

    protected $table = 'recibos_provisionales';
    protected $primaryKey = 'id_recibo_provisional';

    protected $fillable = [
        'lotificacion_id',
        'numero_recibo',
        'codigo_recibo',
        'cliente_nombre',
        'monto',
        'monto_letras',
        'concepto',
        'fecha',
        'motivo',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Retorna el número de recibo formateado según la configuración del proyecto.
     */
    public function getNumeroReciboFormateadoAttribute(): string
    {
        if (!empty($this->codigo_recibo)) {
            return $this->codigo_recibo;
        }

        $lotId = $this->lotificacion_id;
        $prefijo = (string) setting('prefijo_recibo', '', $lotId);
        $longitud = (int) setting('longitud_digitos_recibo', 1, $lotId);
        $numero = $this->numero_recibo ?? $this->id_recibo_provisional;

        $numeroStr = (string) $numero;
        if ($longitud > 1) {
            $numeroStr = str_pad($numeroStr, $longitud, '0', STR_PAD_LEFT);
        }

        return $prefijo . $numeroStr;
    }

    /**
     * Genera el siguiente número consecutivo de recibo para una lotificación,
     * considerando tanto la tabla abonos como la de recibos provisionales.
     */
    public static function generarSiguienteNumeroRecibo(?int $lotificacionId): array
    {
        $tipoNumeracion = setting('tipo_numeracion_recibo', 'proyecto_correlativo', $lotificacionId);
        $prefijo = (string) setting('prefijo_recibo', '', $lotificacionId);
        $longitud = (int) setting('longitud_digitos_recibo', 1, $lotificacionId);
        $numeroInicial = (int) setting('numero_inicial_recibo', 1, $lotificacionId);

        if ($tipoNumeracion === 'proyecto_correlativo' && $lotificacionId) {
            $maxAbonos = Abono::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $lotificacionId))
                ->max('numero_recibo') ?? 0;

            $maxProvisionales = self::withoutGlobalScope('lotificacion')
                ->where('lotificacion_id', $lotificacionId)
                ->max('numero_recibo') ?? 0;

            $maxNumero = max($maxAbonos, $maxProvisionales);
            $siguiente = $maxNumero > 0 ? ($maxNumero + 1) : $numeroInicial;
        } else {
            $maxAbonos = Abono::withoutGlobalScope('lotificacion')->max('id_abono') ?? 0;
            $maxProvisionales = self::withoutGlobalScope('lotificacion')->max('id_recibo_provisional') ?? 0;
            $siguiente = max($maxAbonos, $maxProvisionales) + 1;
        }

        $numStr = (string) $siguiente;
        if ($longitud > 1) {
            $numStr = str_pad($numStr, $longitud, '0', STR_PAD_LEFT);
        }

        return [
            'numero_recibo' => (int) $siguiente,
            'codigo_recibo' => $prefijo . $numStr,
        ];
    }
}
