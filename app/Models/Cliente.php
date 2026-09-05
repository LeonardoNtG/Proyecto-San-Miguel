<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopedByLotificacion;

class Cliente extends Model
{
    use HasFactory, ScopedByLotificacion;
    // 1. Nombre de la tabla
    protected $table = 'clientes';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_cliente';

    // 3. Campos que pueden ser asignados masivamente
    protected $fillable = [
        'expediente_num',
        'pv_num',
        'nombres_apellidos',
        'identificacion', 
        'telefono',
        'direccion',
        'estado_civil',
        'oficio',
        'token_seguimiento',
    ];

    /**
     * Mutator para formatear y guardar la cédula en mayúsculas con formato XXX-XXXXXX-XXXXX
     */
    public function setIdentificacionAttribute($value)
    {
        $clean = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$value), 'UTF-8');
        if (strlen($clean) === 14) {
            $this->attributes['identificacion'] = substr($clean, 0, 3) . '-' . substr($clean, 3, 6) . '-' . substr($clean, 9, 5);
        } else {
            $this->attributes['identificacion'] = mb_strtoupper(trim((string)$value), 'UTF-8');
        }
    }

    public function setNombresApellidosAttribute($value)
    {
        $this->attributes['nombres_apellidos'] = mb_strtoupper(trim((string)$value), 'UTF-8');
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function setEstadoCivilAttribute($value)
    {
        $this->attributes['estado_civil'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function setOficioAttribute($value)
    {
        $this->attributes['oficio'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function setExpedienteNumAttribute($value)
    {
        $this->attributes['expediente_num'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function setPvNumAttribute($value)
    {
        $this->attributes['pv_num'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public static function generarSiguienteExpediente()
    {
        $ultimoCliente = static::withoutGlobalScope('lotificacion')
            ->orderBy('id_cliente', 'desc')
            ->first();

        $siguienteNumero = 1;
        if ($ultimoCliente) {
            preg_match('/(\d+)/', $ultimoCliente->expediente_num ?? '', $matches);
            if (!empty($matches[1])) {
                $numeroExtraido = intval($matches[1]);
                $siguienteNumero = max($numeroExtraido + 1, $ultimoCliente->id_cliente + 1);
            } else {
                $siguienteNumero = $ultimoCliente->id_cliente + 1;
            }
        }

        do {
            $codigo = 'EXP-' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);
            $existe = static::withoutGlobalScope('lotificacion')->where('expediente_num', $codigo)->exists();
            if ($existe) {
                $siguienteNumero++;
            }
        } while ($existe);

        return $codigo;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('lotificacion')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            if (empty($cliente->token_seguimiento)) {
                $cliente->token_seguimiento = \Illuminate\Support\Str::uuid()->toString();
            }
            if (empty($cliente->expediente_num)) {
                $cliente->expediente_num = static::generarSiguienteExpediente();
            }
            if (empty($cliente->pv_num)) {
                $cliente->pv_num = 'PP';
            }
        });
    }
    
    // 4. Relación: Un Cliente puede tener muchas Ventas (Promesas de Venta)
    public function ventas()
    {
        // La clave foránea en la tabla 'ventas' es 'id_cliente'
        return $this->hasMany(Venta::class, 'id_cliente', 'id_cliente');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_cliente', 'id_cliente');
    }

    public function rescisiones()
    {
        return $this->hasMany(Rescision::class, 'id_cliente', 'id_cliente')->orderBy('created_at', 'desc');
    }
}
