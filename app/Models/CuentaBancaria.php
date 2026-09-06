<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'banco',
        'moneda',
        'numero_cuenta',
        'titular',
        'estado',
        'lotificacion_id',
    ];

    /**
     * Retorna una representación formateada para selects y comprobantes.
     * Ejemplo: "Banpro - $ • 10021210290831 - Ángeles Nazareth Cruz"
     */
    public function getTextoCompletoAttribute(): string
    {
        return "{$this->banco} - {$this->moneda} • {$this->numero_cuenta} - {$this->titular}";
    }

    /**
     * Scope para filtrar solo cuentas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'Activa')->orderBy('banco');
    }

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class);
    }
}
