<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accion',
        'modelo',
        'modelo_id',
        'detalles',
        'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el cliente relacionado según el modelo auditado.
     */
    public function getClienteAttribute()
    {
        try {
            if ($this->modelo === 'Cliente' && $this->modelo_id) {
                return Cliente::withoutGlobalScopes()->find($this->modelo_id);
            }
            if ($this->modelo === 'Venta' && $this->modelo_id) {
                $venta = Venta::withoutGlobalScopes()->with('cliente')->find($this->modelo_id);
                return $venta ? $venta->cliente : null;
            }
            if ($this->modelo === 'Abono' && $this->modelo_id) {
                $abono = Abono::withoutGlobalScopes()->with('venta.cliente')->find($this->modelo_id);
                return $abono && $abono->venta ? $abono->venta->cliente : null;
            }
            if ($this->modelo === 'Rescision' && $this->modelo_id) {
                $rescision = Rescision::with('cliente')->find($this->modelo_id);
                return $rescision ? $rescision->cliente : null;
            }
            if ($this->modelo === 'Cuota' && $this->modelo_id) {
                $cuota = Cuota::with('venta.cliente')->find($this->modelo_id);
                return $cuota && $cuota->venta ? $cuota->venta->cliente : null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /**
     * Obtener los lotes relacionados según el modelo auditado.
     */
    public function getLotesAttribute()
    {
        try {
            if ($this->modelo === 'Venta' && $this->modelo_id) {
                $venta = Venta::withoutGlobalScopes()->with('lotes.bloque')->find($this->modelo_id);
                if ($venta && $venta->lotes) {
                    return $venta->lotes->map(fn($l) => ($l->bloque ? $l->bloque->nombre . ' - ' : '') . 'Lote ' . $l->numero_lote)->implode(', ');
                }
            }
            if ($this->modelo === 'Abono' && $this->modelo_id) {
                $abono = Abono::withoutGlobalScopes()->with('venta.lotes.bloque')->find($this->modelo_id);
                if ($abono && $abono->venta && $abono->venta->lotes) {
                    return $abono->venta->lotes->map(fn($l) => ($l->bloque ? $l->bloque->nombre . ' - ' : '') . 'Lote ' . $l->numero_lote)->implode(', ');
                }
            }
            if ($this->modelo === 'Rescision' && $this->modelo_id) {
                $rescision = Rescision::find($this->modelo_id);
                return $rescision ? $rescision->lotes_afectados : null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /**
     * Color distintivo para la insignia de acción.
     */
    public function getBadgeClassAttribute()
    {
        $a = mb_strtolower($this->accion);
        if (str_contains($a, 'abono') || str_contains($a, 'pago') || str_contains($a, 'creó') || str_contains($a, 'creo')) return 'bg-success text-white';
        if (str_contains($a, 'edición') || str_contains($a, 'edicion') || str_contains($a, 'modific') || str_contains($a, 'actualiz')) return 'bg-warning text-dark';
        if (str_contains($a, 'elimin') || str_contains($a, 'rescisi') || str_contains($a, 'anul') || str_contains($a, 'borr')) return 'bg-danger text-white';
        if (str_contains($a, 'exoner') || str_contains($a, 'mora')) return 'bg-purple text-white' ;
        if (str_contains($a, 'recalcul')) return 'bg-secondary text-white';
        return 'bg-primary text-white';
    }

    /**
     * Icono representativo de la acción.
     */
    public function getIconAttribute()
    {
        $a = mb_strtolower($this->accion);
        if (str_contains($a, 'abono') || str_contains($a, 'pago')) return 'fas fa-cash-register';
        if (str_contains($a, 'edición') || str_contains($a, 'edicion') || str_contains($a, 'modific')) return 'fas fa-edit';
        if (str_contains($a, 'elimin') || str_contains($a, 'borr')) return 'fas fa-trash-alt';
        if (str_contains($a, 'rescisi')) return 'fas fa-ban';
        if (str_contains($a, 'exoner')) return 'fas fa-percent';
        if (str_contains($a, 'recalcul')) return 'fas fa-calculator';
        return 'fas fa-history';
    }

    /**
     * Helper to log an action easily.
     */
    public static function log($accion, $modelo = null, $modelo_id = null, $detalles = null)
    {
        self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'modelo' => $modelo,
            'modelo_id' => $modelo_id,
            'detalles' => $detalles,
            'ip_address' => request()->ip(),
        ]);
    }
}
