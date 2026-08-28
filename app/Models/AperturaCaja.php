<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AperturaCaja extends Model
{
    use HasFactory;
    
    protected $table = 'apertura_cajas';

    protected $fillable = [
        'fecha',
        'monto_inicial',
        'user_id'
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'fecha' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
