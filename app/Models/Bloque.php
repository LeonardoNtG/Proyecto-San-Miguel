<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloque extends Model
{
    // Nombre de la tabla
    protected $table = 'bloques';

    // Clave primaria personalizada (si es diferente a 'id')
    protected $primaryKey = 'id_bloque';

    // Campos que pueden ser asignados masivamente (Mass Assignable)
    protected $fillable = [
        'nombre',
        'proyecto',
        'descripcion',
    ];

    // Relación: Un Bloque tiene muchos Lotes (One-to-Many)
    // El 'foreign key' es el 'id_bloque' en la tabla 'lotes'
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'id_bloque', 'id_bloque');
    }
}
