<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Bloque;
use App\Models\Lote;
use App\Models\HistorialLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    public function index()
    {
        $reservas = Reserva::with('cliente', 'lotes')->orderBy('created_at', 'desc')->get();
        return view('reservas.index', compact('reservas'));
    }

    public function create()
    {
        $activeLotificacionId = session('lotificacion_id');
        $lotificacionActiva = \App\Models\Lotificacion::find($activeLotificacionId);
        $bloques = Bloque::where('lotificacion_id', $activeLotificacionId)->orderBy('nombre')->get();

        return view('reservas.create', compact('lotificacionActiva', 'bloques'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres_apellidos' => 'required|string|max:100',
            'identificacion' => 'required|string|max:20',
            'lotes_ids' => 'required|array',
            'lotes_ids.*' => 'exists:lotes,id_lote',
            'monto_reserva' => 'nullable|numeric|min:0',
            'dias_validez' => 'nullable|integer|min:1',
            'metodo_pago' => 'nullable|string',
        ]);

        $montoReserva = floatval($request->input('monto_reserva', 0) ?: 0);
        $diasValidez = intval($request->input('dias_validez', 5) ?: 5);

        $activeLotificacionId = session('lotificacion_id');
        $lotificacionId = $activeLotificacionId ?: $request->lotificacion_id;

        DB::beginTransaction();

        try {
            // Check if cliente exists or create it
            $cliente = Cliente::where('identificacion', $request->identificacion)->first();
            if (!$cliente) {
                $cliente = Cliente::create([
                    'nombres_apellidos' => $request->nombres_apellidos,
                    'identificacion' => $request->identificacion,
                    'telefono' => $request->telefono ?? 'N/D',
                    'direccion' => $request->direccion ?? 'N/D',
                    'oficio' => $request->oficio ?? $request->profesion_oficio,
                    'estado_civil' => $request->estado_civil,
                ]);
            }

            $reserva = Reserva::create([
                'id_cliente' => $cliente->id_cliente,
                'lotificacion_id' => $lotificacionId,
                'monto_reserva' => $montoReserva,
                'fecha_reserva' => now()->format('Y-m-d'),
                'fecha_vencimiento' => now()->addDays($diasValidez)->format('Y-m-d'),
                'estado' => 'Activa',
            ]);

            Lote::whereIn('id_lote', $request->lotes_ids)->update(['estado' => 'Reservado']);

            foreach ($request->lotes_ids as $loteId) {
                HistorialLote::create([
                    'id_lote' => $loteId,
                    'id_reserva' => $reserva->id_reserva,
                    'estado' => 'Reservado',
                    'fecha_asignacion' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('reservas.index')->with('success', 'Reserva registrada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar reserva: ' . $e->getMessage());
        }
    }

    public function anular(Reserva $reserva)
    {
        if ($reserva->estado != 'Activa') {
            return redirect()->back()->with('error', 'Solo las reservas activas pueden ser anuladas.');
        }

        DB::beginTransaction();
        try {
            $reserva->update(['estado' => 'Anulada']);
            
            // Liberar lotes
            foreach ($reserva->lotes as $lote) {
                $lote->update(['estado' => 'Disponible']);
                HistorialLote::where('id_lote', $lote->id_lote)
                             ->where('id_reserva', $reserva->id_reserva)
                             ->update([
                                 'estado' => 'Rescindido',
                                 'fecha_liberacion' => now(),
                             ]);
            }

            DB::commit();
            \App\Models\Auditoria::log('Anuló Reserva', 'Reserva', $reserva->id_reserva, 'Reserva y lotes liberados');
            return redirect()->back()->with('success', 'Reserva anulada. Lotes liberados.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function formalizar(Reserva $reserva)
    {
        if ($reserva->estado != 'Activa') {
            return redirect()->route('reservas.index')->with('error', 'La reserva ya fue procesada o anulada.');
        }

        return view('reservas.formalizar', compact('reserva'));
    }

    public function procesarFormalizacion(Request $request, Reserva $reserva)
    {
        if ($reserva->estado != 'Activa') {
            return redirect()->route('reservas.index')->with('error', 'La reserva ya fue procesada o anulada.');
        }

        $request->validate([
            'lotes_a_formalizar' => 'required|array|min:1',
            'lotes_a_formalizar.*' => 'exists:lotes,id_lote',
            'precio_final' => 'required|numeric|min:0',
            'plazo_meses' => 'required|integer|min:1',
            'cuota_mensual' => 'required|numeric|min:0',
            'primer_abono' => 'required|numeric|min:0',
            'fecha_ultimo_abono' => 'nullable|date',
            'metodo_pago' => 'nullable|string',
            'referencia' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $lotesFormalizarIds = $request->lotes_a_formalizar;
            $extensionTotal = \App\Models\Lote::whereIn('id_lote', $lotesFormalizarIds)->sum('area_metros');

            // 1. Crear la Venta
            $venta = \App\Models\Venta::create([
                'id_cliente' => $reserva->id_cliente,
                'lotificacion_id' => $reserva->lotificacion_id,
                'fecha_venta' => now()->format('Y-m-d'),
                'precio_final' => $request->precio_final,
                'plazo_meses' => $request->plazo_meses,
                'estado_contrato' => 'Vigente',
                'extension_lote' => $extensionTotal,
                'cuota_mensual' => $request->cuota_mensual,
            ]);

            // 2. Transición de Lotes:
            // A. Lotes formalizados -> Pasan a 'Vendido' y se transfieren a la Venta
            \App\Models\Lote::whereIn('id_lote', $lotesFormalizarIds)->update(['estado' => 'Vendido']);
            HistorialLote::whereIn('id_lote', $lotesFormalizarIds)
                ->where('id_reserva', $reserva->id_reserva)
                ->update([
                    'id_venta' => $venta->id_venta,
                    'estado' => 'Activo',
                ]);

            // B. Lotes de la reserva no seleccionados -> Se liberan automáticamente a 'Disponible'
            $todosLotesReserva = $reserva->lotes->pluck('id_lote')->toArray();
            $lotesALiberar = array_values(array_diff($todosLotesReserva, $lotesFormalizarIds));

            if (!empty($lotesALiberar)) {
                \App\Models\Lote::whereIn('id_lote', $lotesALiberar)->update(['estado' => 'Disponible']);
                HistorialLote::whereIn('id_lote', $lotesALiberar)
                    ->where('id_reserva', $reserva->id_reserva)
                    ->update([
                        'estado' => 'Rescindido',
                        'fecha_liberacion' => now(),
                    ]);
            }

            // 3. Crear el primer abono (Prima)
            $abonoInicial = \App\Models\Abono::create([
                'id_venta' => $venta->id_venta,
                'fecha_pago' => $request->fecha_ultimo_abono ?? now(),
                'monto_abonado' => $request->primer_abono,
                'tipo_pago' => 'Prima/Primer Abono',
                'metodo_pago' => $request->metodo_pago ?? 'Efectivo',
                'referencia' => $request->referencia ?? ('Formalización de Reserva #' . $reserva->id_reserva),
                'user_id' => auth()->id(),
            ]);

            // 4. Generar el Plan de Pagos (Cuotas)
            $plazoRestante = $venta->plazo_meses;
            $saldoRestante = $venta->precio_final;
            $cuotaMensual = $venta->cuota_mensual;

            if ($plazoRestante > 0 && $saldoRestante > 0) {
                $fechaVencimiento = \Carbon\Carbon::parse($abonoInicial->fecha_pago);
                for ($i = 1; $i <= $plazoRestante; $i++) {
                    $fechaVencimiento->addMonth();
                    $montoCuota = ($i == $plazoRestante) ? $saldoRestante : $cuotaMensual;
                    
                    \App\Models\Cuota::create([
                        'id_venta' => $venta->id_venta,
                        'numero_cuota' => $i,
                        'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                        'monto_total' => $montoCuota,
                        'capital' => $montoCuota,
                        'interes' => 0,
                        'saldo_restante' => $montoCuota,
                        'estado' => 'Pendiente',
                    ]);
                    
                    $saldoRestante -= $montoCuota;
                }
            }

            // Aplicar el abono inicial automáticamente a las cuotas
            \App\Http\Controllers\AbonoController::recalcularCuotas($venta->id_venta);

            // 5. Marcar reserva como Formalizada
            $reserva->update(['estado' => 'Formalizada']);

            DB::commit();
            \App\Models\Auditoria::log('Formalizó Reserva', 'Reserva', $reserva->id_reserva, 'Formalizada en Venta #'.$venta->id_venta);
            return redirect()->route('clientes.show', $reserva->id_cliente)->with('success', 'Reserva formalizada exitosamente. La venta ha sido registrada.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al formalizar: ' . $e->getMessage());
        }
    }
}
