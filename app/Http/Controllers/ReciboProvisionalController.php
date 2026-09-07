<?php

namespace App\Http\Controllers;

use App\Models\Lotificacion;
use App\Models\ReciboProvisional;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReciboProvisionalController extends Controller
{
    private function ensureTableExists()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('recibos_provisionales')) {
                \Illuminate\Support\Facades\Schema::create('recibos_provisionales', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id('id_recibo_provisional');
                    $table->unsignedBigInteger('lotificacion_id')->nullable();
                    $table->unsignedBigInteger('numero_recibo')->nullable();
                    $table->string('codigo_recibo', 50)->nullable();
                    $table->string('cliente_nombre')->nullable();
                    $table->decimal('monto', 12, 2)->nullable();
                    $table->string('monto_letras')->nullable();
                    $table->text('concepto')->nullable();
                    $table->decimal('valor_total', 12, 2)->nullable();
                    $table->decimal('total_abonado', 12, 2)->nullable();
                    $table->decimal('saldo_pendiente', 12, 2)->nullable();
                    $table->date('fecha');
                    $table->text('motivo')->nullable();
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->timestamps();
                });
            } else {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('recibos_provisionales', 'valor_total')) {
                    \Illuminate\Support\Facades\Schema::table('recibos_provisionales', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->decimal('valor_total', 12, 2)->nullable()->after('concepto');
                        $table->decimal('total_abonado', 12, 2)->nullable()->after('valor_total');
                        $table->decimal('saldo_pendiente', 12, 2)->nullable()->after('total_abonado');
                    });
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error in recibos_provisionales schema: ' . $e->getMessage());
        }
    }

    /**
     * Muestra la lista de recibos provisionales generados manualmente y el formulario de emisión.
     */
    public function index(Request $request)
    {
        $this->ensureTableExists();

        $query = ReciboProvisional::withoutGlobalScope('lotificacion')
            ->with(['lotificacion', 'user'])
            ->orderBy('id_recibo_provisional', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('codigo_recibo', 'like', "%{$search}%")
                  ->orWhere('numero_recibo', 'like', "%{$search}%")
                  ->orWhere('cliente_nombre', 'like', "%{$search}%")
                  ->orWhere('concepto', 'like', "%{$search}%")
                  ->orWhere('motivo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('lotificacion_id')) {
            $query->where('lotificacion_id', $request->input('lotificacion_id'));
        }

        $recibos = $query->paginate(20)->withQueryString();
        $lotificaciones = Lotificacion::all();

        return view('recibos_provisionales.index', compact('recibos', 'lotificaciones'));
    }

    /**
     * Guarda un nuevo recibo provisional en la base de datos y lo abre para impresión.
     */
    public function store(Request $request)
    {
        $this->ensureTableExists();

        $lotificacionId = $request->input('lotificacion_id') ?: Lotificacion::first()?->id;
        $lotificacion = $lotificacionId ? Lotificacion::find($lotificacionId) : null;

        $dejarEnBlanco = $request->boolean('dejar_en_blanco');

        if ($dejarEnBlanco) {
            $clienteNombre  = null;
            $monto          = null;
            $montoLetras    = null;
            $concepto       = null;
            $valorTotal     = null;
            $totalAbonado   = null;
            $saldoPendiente = null;
        } else {
            $clienteNombre  = $request->filled('cliente_nombre') ? trim($request->input('cliente_nombre')) : null;
            $monto          = $request->filled('monto') ? (float) $request->input('monto') : null;
            $montoLetras    = ($monto && $monto > 0) ? $this->convertirMontoALetras($monto) : null;
            $concepto       = $request->filled('concepto') ? trim($request->input('concepto')) : null;
            $valorTotal     = $request->filled('valor_total') ? (float) $request->input('valor_total') : null;
            $totalAbonado   = $request->filled('total_abonado') ? (float) $request->input('total_abonado') : null;
            $abonosAnteriores = $request->filled('abonos_anteriores') ? (float) $request->input('abonos_anteriores') : null;

            if ($totalAbonado === null && ($abonosAnteriores !== null || $monto !== null)) {
                $totalAbonado = ($abonosAnteriores ?? 0) + ($monto ?? 0);
            }

            $saldoPendiente = null;
            if ($valorTotal !== null) {
                $saldoPendiente = max(0, $valorTotal - ($totalAbonado ?? ($monto ?? 0)));
            }
        }

        $fecha  = $request->filled('fecha') ? $request->input('fecha') : now()->format('Y-m-d');
        $motivo = $request->filled('motivo') ? trim($request->input('motivo')) : null;

        // Generar siguiente correlativo
        $reciboData = ReciboProvisional::generarSiguienteNumeroRecibo($lotificacionId);

        $recibo = ReciboProvisional::create([
            'lotificacion_id' => $lotificacionId,
            'numero_recibo'   => $reciboData['numero_recibo'],
            'codigo_recibo'   => $reciboData['codigo_recibo'],
            'cliente_nombre'  => $clienteNombre,
            'monto'           => $monto,
            'monto_letras'    => $montoLetras,
            'concepto'        => $concepto,
            'valor_total'     => $valorTotal,
            'total_abonado'   => $totalAbonado,
            'saldo_pendiente' => $saldoPendiente,
            'fecha'           => $fecha,
            'motivo'          => $motivo,
            'user_id'         => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'recibo'       => $recibo,
                'imprimir_url' => route('recibos_provisionales.imprimir', $recibo->id_recibo_provisional),
            ]);
        }

        return redirect()->route('recibos_provisionales.index')
            ->with('success', 'Recibo Provisional N° ' . ($recibo->numero_recibo_formateado) . ' generado exitosamente.')
            ->with('imprimir_provisional_id', $recibo->id_recibo_provisional);
    }

    /**
     * Actualiza un recibo provisional existente para corregir errores.
     */
    public function update(Request $request, $id)
    {
        $this->ensureTableExists();

        $recibo = ReciboProvisional::withoutGlobalScope('lotificacion')->findOrFail($id);

        $dejarEnBlanco = $request->boolean('dejar_en_blanco');

        if ($dejarEnBlanco) {
            $clienteNombre  = null;
            $monto          = null;
            $montoLetras    = null;
            $concepto       = null;
            $valorTotal     = null;
            $totalAbonado   = null;
            $saldoPendiente = null;
        } else {
            $clienteNombre  = $request->filled('cliente_nombre') ? trim($request->input('cliente_nombre')) : null;
            $monto          = $request->filled('monto') ? (float) $request->input('monto') : null;
            $montoLetras    = ($monto && $monto > 0) ? $this->convertirMontoALetras($monto) : null;
            $concepto       = $request->filled('concepto') ? trim($request->input('concepto')) : null;
            $valorTotal     = $request->filled('valor_total') ? (float) $request->input('valor_total') : null;
            $totalAbonado   = $request->filled('total_abonado') ? (float) $request->input('total_abonado') : null;
            $abonosAnteriores = $request->filled('abonos_anteriores') ? (float) $request->input('abonos_anteriores') : null;

            if ($totalAbonado === null && ($abonosAnteriores !== null || $monto !== null)) {
                $totalAbonado = ($abonosAnteriores ?? 0) + ($monto ?? 0);
            }

            $saldoPendiente = null;
            if ($valorTotal !== null) {
                $saldoPendiente = max(0, $valorTotal - ($totalAbonado ?? ($monto ?? 0)));
            }
        }

        $fecha  = $request->filled('fecha') ? $request->input('fecha') : $recibo->fecha;
        $motivo = $request->filled('motivo') ? trim($request->input('motivo')) : null;

        if ($request->filled('lotificacion_id')) {
            $recibo->lotificacion_id = $request->input('lotificacion_id');
        }

        $recibo->update([
            'lotificacion_id' => $recibo->lotificacion_id,
            'cliente_nombre'  => $clienteNombre,
            'monto'           => $monto,
            'monto_letras'    => $montoLetras,
            'concepto'        => $concepto,
            'valor_total'     => $valorTotal,
            'total_abonado'   => $totalAbonado,
            'saldo_pendiente' => $saldoPendiente,
            'fecha'           => $fecha,
            'motivo'          => $motivo,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'recibo'       => $recibo,
                'imprimir_url' => route('recibos_provisionales.imprimir', $recibo->id_recibo_provisional),
            ]);
        }

        return redirect()->route('recibos_provisionales.index')
            ->with('success', "Recibo Provisional N° {$recibo->numero_recibo_formateado} actualizado correctamente.")
            ->with('imprimir_provisional_id', $recibo->id_recibo_provisional);
    }

    /**
     * Muestra la vista de impresión del recibo provisional (oculta cálculos y QR).
     */
    public function imprimir($id)
    {
        $recibo = ReciboProvisional::withoutGlobalScope('lotificacion')
            ->with(['lotificacion', 'user'])
            ->findOrFail($id);

        $lotificacion = $recibo->lotificacion ?: Lotificacion::first();
        $sufijoMoneda = (string) setting('sufijo_moneda_letras', 'DÓLARES NETOS', $lotificacion?->id);

        $imprimirDoble = (bool) setting('imprimir_doble_recibo', true, $lotificacion?->id);

        return view('recibos_provisionales.imprimir', [
            'recibo'              => $recibo,
            'lotificacion'        => $lotificacion,
            'numeroReciboMostrar' => $recibo->numero_recibo_formateado,
            'sufijoMoneda'        => $sufijoMoneda,
            'imprimirDoble'       => $imprimirDoble,
            'leyendaPie'          => (string) setting('leyenda_pie_recibo', 'Conserve este comprobante como constancia legal de su pago.', $lotificacion?->id),
        ]);
    }

    /**
     * Elimina un recibo provisional generado.
     */
    public function destroy($id)
    {
        $recibo = ReciboProvisional::withoutGlobalScope('lotificacion')->findOrFail($id);
        $numero = $recibo->numero_recibo_formateado;
        $recibo->delete();

        return redirect()->route('recibos_provisionales.index')
            ->with('success', "Recibo Provisional N° {$numero} eliminado correctamente.");
    }

    /**
     * Convierte el monto numérico a texto en palabras.
     */
    private function convertirMontoALetras($monto)
    {
        $monto = number_format((float) $monto, 2, '.', '');
        [$entero, $decimal] = explode('.', $monto);

        $entero = (int) $entero;
        $decimal = (int) $decimal;

        $texto = strtoupper($this->numeroALetras($entero));

        if ($decimal > 0) {
            $texto .= ' CON ' . strtoupper($this->numeroALetras($decimal)) . ' CENTAVOS';
        }

        return $texto;
    }

    private function numeroALetras($numero)
    {
        $unidades = [
            '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
            'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte'
        ];

        $decenas = [
            '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'
        ];

        $centenas = [
            '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
            'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
        ];

        if ($numero == 0) return 'cero';
        if ($numero == 100) return 'cien';

        if ($numero <= 20) {
            return $unidades[$numero];
        }

        if ($numero < 100) {
            $d = (int)($numero / 10);
            $u = $numero % 10;
            if ($d == 2 && $u > 0) {
                return 'veinti' . $unidades[$u];
            }
            return $decenas[$d] . ($u > 0 ? ' y ' . $unidades[$u] : '');
        }

        if ($numero < 1000) {
            $c = (int)($numero / 100);
            $resto = $numero % 100;
            return $centenas[$c] . ($resto > 0 ? ' ' . $this->numeroALetras($resto) : '');
        }

        if ($numero < 1000000) {
            $miles = (int)($numero / 1000);
            $resto = $numero % 1000;
            $textoMiles = ($miles == 1) ? 'mil' : $this->numeroALetras($miles) . ' mil';
            return $textoMiles . ($resto > 0 ? ' ' . $this->numeroALetras($resto) : '');
        }

        if ($numero < 1000000000) {
            $millones = (int)($numero / 1000000);
            $resto = $numero % 1000000;
            $textoMillones = ($millones == 1) ? 'un millón' : $this->numeroALetras($millones) . ' millones';
            return $textoMillones . ($resto > 0 ? ' ' . $this->numeroALetras($resto) : '');
        }

        return (string) $numero;
    }

    /**
     * Muestra la vista interactiva de Cierre Diario de Recibos Provisionales.
     */
    public function cierre(Request $request)
    {
        $this->ensureTableExists();

        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));
        $lotificacionId = $request->input('lotificacion_id');

        $query = ReciboProvisional::withoutGlobalScope('lotificacion')
            ->with(['lotificacion', 'user'])
            ->where(function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha)
                  ->orWhereDate('created_at', $fecha);
            });

        if ($lotificacionId) {
            $query->where('lotificacion_id', $lotificacionId);
        }

        $recibos = $query->orderBy('numero_recibo', 'asc')->get();

        $totalRecibos = $recibos->count();
        $totalMonto = (float) $recibos->sum('monto');
        $recibosConMonto = $recibos->filter(fn($r) => !is_null($r->monto) && (float)$r->monto > 0)->count();
        $recibosEnBlanco = $totalRecibos - $recibosConMonto;

        // Desglose por proyecto / lotificación
        $porProyecto = $recibos->groupBy(fn($r) => $r->lotificacion?->nombre ?? 'Sin Proyecto')->map(function ($items, $key) {
            return [
                'nombre'   => $key,
                'cantidad' => $items->count(),
                'total'    => (float) $items->sum('monto'),
            ];
        });

        // Desglose por cajero / usuario
        $porUsuario = $recibos->groupBy(fn($r) => $r->user?->name ?? 'Usuario Sistema')->map(function ($items, $key) {
            return [
                'nombre'   => $key,
                'cantidad' => $items->count(),
                'total'    => (float) $items->sum('monto'),
            ];
        });

        $lotificaciones = Lotificacion::all();

        return view('recibos_provisionales.cierre', compact(
            'recibos',
            'fecha',
            'lotificacionId',
            'lotificaciones',
            'totalRecibos',
            'totalMonto',
            'recibosConMonto',
            'recibosEnBlanco',
            'porProyecto',
            'porUsuario'
        ));
    }

    /**
     * Muestra la versión imprimible / PDF del Cierre Diario de Recibos Provisionales.
     */
    public function imprimirCierrePdf(Request $request)
    {
        $this->ensureTableExists();

        $fecha = $request->input('fecha', Carbon::today()->format('Y-m-d'));
        $lotificacionId = $request->input('lotificacion_id');

        $query = ReciboProvisional::withoutGlobalScope('lotificacion')
            ->with(['lotificacion', 'user'])
            ->where(function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha)
                  ->orWhereDate('created_at', $fecha);
            });

        if ($lotificacionId) {
            $query->where('lotificacion_id', $lotificacionId);
        }

        $recibos = $query->orderBy('numero_recibo', 'asc')->get();

        $totalRecibos = $recibos->count();
        $totalMonto = (float) $recibos->sum('monto');
        $recibosConMonto = $recibos->filter(fn($r) => !is_null($r->monto) && (float)$r->monto > 0)->count();
        $recibosEnBlanco = $totalRecibos - $recibosConMonto;
        $montoEnLetras = $this->convertirMontoALetras($totalMonto);

        $lotificacion = $lotificacionId ? Lotificacion::find($lotificacionId) : null;

        return view('recibos_provisionales.cierre_pdf', compact(
            'recibos',
            'fecha',
            'lotificacion',
            'totalRecibos',
            'totalMonto',
            'recibosConMonto',
            'recibosEnBlanco',
            'montoEnLetras'
        ));
    }
}
