<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    
    use HasFactory, \App\Traits\ScopedByLotificacion;



    protected $table = 'ventas';

    //  Clave primaria personalizada
    protected $primaryKey = 'id_venta';

    // Campos que deben permitir asignación masiva
    protected $fillable = [
        'id_cliente',
        'lotificacion_id',
        'fecha_venta',
        'precio_final',
        'plazo_meses',
        'estado_contrato',
        'extension_lote',
        'cuota_mensual',
        'beneficiario_final',
        'nota_beneficiario',
    ];

    public function setBeneficiarioFinalAttribute($value)
    {
        $this->attributes['beneficiario_final'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function setNotaBeneficiarioAttribute($value)
    {
        $this->attributes['nota_beneficiario'] = $value ? mb_strtoupper(trim((string)$value), 'UTF-8') : null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('lotificacion')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    // Relación: Una Venta pertenece a un Cliente (Many-to-One)
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'id_venta', 'id_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_venta', 'id_venta');
    }

    public function historialLotes()
    {
        return $this->hasMany(HistorialLote::class, 'id_venta', 'id_venta');
    }

    public function lotes()
    {
        return $this->belongsToMany(Lote::class, 'historial_lotes', 'id_venta', 'id_lote')
                    ->wherePivot('estado', 'Activo')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion')
                    ->withTimestamps();
    }

    public function lotesRescindidos()
    {
        return $this->belongsToMany(Lote::class, 'historial_lotes', 'id_venta', 'id_lote')
                    ->wherePivot('estado', 'Rescindido')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion')
                    ->withTimestamps();
    }

    public function todosLotes()
    {
        return $this->belongsToMany(Lote::class, 'historial_lotes', 'id_venta', 'id_lote')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_liberacion')
                    ->withTimestamps();
    }

    public function rescisiones()
    {
        return $this->hasMany(Rescision::class, 'id_venta', 'id_venta')->orderBy('created_at', 'desc');
    }



    public function getLoteAttribute()
    {
        return $this->lotes->first();
    }

    // Nombre del proyecto/lotificación asociado
    public function getProyectoAttribute()
    {
        return $this->lotificacion?->nombre 
            ?? $this->lotes?->first()?->bloque?->lotificacion?->nombre 
            ?? $this->lotes?->first()?->bloque?->proyecto 
            ?? '—';
    }

    // Cantidad de lotes asociados a esta venta (usado en las vistas)
    public function getTotalLotesVendidosAttribute()
    {
        return $this->lotes()->count();
    }
}
