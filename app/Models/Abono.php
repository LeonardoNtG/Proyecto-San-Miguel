<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;
    
    // Nombre de la tabla
    protected $table = 'abonos';

    // Clave primaria personalizada
    protected $primaryKey = 'id_abono';

    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'id_venta',
        'numero_recibo',
        'codigo_recibo',
        'monto_abonado',
        'fecha_pago',
        'tipo_pago',
        'metodo_pago',
        'referencia',
        'cuenta_destino',
        'ruta_recibo',
        'user_id'
    ];
    
    // Relación: Un Abono pertenece a una Venta (Many-to-One)
    public function venta()
    {
        // La clave foránea en la tabla 'abonos' es 'id_venta'
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    // Relación: Un Abono pertenece a un Usuario (Many-to-One)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna el número de recibo formateado según la configuración del proyecto.
     */
    public function getNumeroReciboFormateadoAttribute(): string
    {
        $lotId = $this->venta?->lotificacion_id;
        $tipoNumeracion = setting('tipo_numeracion_recibo', 'proyecto_correlativo', $lotId);
        $prefijo = (string) setting('prefijo_recibo', '', $lotId);
        $longitud = (int) setting('longitud_digitos_recibo', 1, $lotId);

        $numero = ($tipoNumeracion === 'proyecto_correlativo')
            ? ($this->numero_recibo ?? $this->id_abono)
            : $this->id_abono;

        $numeroStr = (string) $numero;
        if ($longitud > 1) {
            $numeroStr = str_pad($numeroStr, $longitud, '0', STR_PAD_LEFT);
        }

        return $prefijo . $numeroStr;
    }

    /**
     * Genera el siguiente número correlativo y código de recibo para una lotificación dada.
     */
    public static function generarSiguienteNumeroRecibo(int $lotificacionId): array
    {
        $tipoNumeracion = setting('tipo_numeracion_recibo', 'proyecto_correlativo', $lotificacionId);
        $prefijo = (string) setting('prefijo_recibo', '', $lotificacionId);
        $longitud = (int) setting('longitud_digitos_recibo', 1, $lotificacionId);
        $numeroInicial = (int) setting('numero_inicial_recibo', 1, $lotificacionId);

        if ($tipoNumeracion === 'proyecto_correlativo') {
            $maxNumero = self::withoutGlobalScope('lotificacion')
                ->whereHas('venta', fn($q) => $q->withoutGlobalScope('lotificacion')->where('lotificacion_id', $lotificacionId))
                ->max('numero_recibo');

            $siguiente = $maxNumero ? ($maxNumero + 1) : $numeroInicial;
        } else {
            $ultimoId = self::withoutGlobalScope('lotificacion')->max('id_abono');
            $siguiente = $ultimoId ? ($ultimoId + 1) : 1;
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
