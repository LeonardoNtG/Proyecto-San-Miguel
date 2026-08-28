<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloque extends Model
{
    use HasFactory, \App\Traits\ScopedByLotificacion;

    // Nombre de la tabla
    protected $table = 'bloques';

    // Clave primaria personalizada (si es diferente a 'id')
    protected $primaryKey = 'id_bloque';

    // Campos que pueden ser asignados masivamente (Mass Assignable)
    protected $fillable = [
        'nombre',
        'lotificacion_id',
        'descripcion',
    ];

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    // Relación: Un Bloque tiene muchos Lotes (One-to-Many)
    // El 'foreign key' es el 'id_bloque' en la tabla 'lotes'
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'id_bloque', 'id_bloque');
    }
}
