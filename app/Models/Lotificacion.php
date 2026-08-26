<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lotificacion extends Model
{
    use HasFactory;

    protected $table = 'lotificaciones';

    protected $fillable = [
        'nombre',
        'ubicacion',
        'descripcion',
        'logo',
        'ruc',
        'telefono',
        'ciudad'
    ];

    public function bloques()
    {
        return $this->hasMany(Bloque::class, 'lotificacion_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'lotificacion_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'lotificacion_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'lotificacion_user');
    }
}
