<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloque extends Model
{
    // 1. Nombre de la tabla
    protected $table = 'bloques';

    // 2. Clave primaria personalizada (si es diferente a 'id')
    protected $primaryKey = 'id_bloque';

    // 3. Campos que pueden ser asignados masivamente (Mass Assignable)
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // 4. Relación: Un Bloque tiene muchos Lotes (One-to-Many)
    // El 'foreign key' es el 'id_bloque' en la tabla 'lotes'
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'id_bloque', 'id_bloque');
    }
}
