<?php

namespace App\Http\Controllers;

use App\Models\Lotificacion;
use App\Models\ReciboProvisional;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReciboProvisionalController extends Controller
{
    /**
     * Muestra la lista de recibos provisionales generados manualmente y el formulario de emisión.
     */
    public function index(Request $request)
    {
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
        $lotificacionId = $request->input('lotificacion_id') ?: Lotificacion::first()?->id;
        $lotificacion = $lotificacionId ? Lotificacion::find($lotificacionId) : null;

        $dejarEnBlanco = $request->boolean('dejar_en_blanco');

        if ($dejarEnBlanco) {
            $clienteNombre = null;
            $monto = null;
            $montoLetras = null;
            $concepto = null;
        } else {
            $clienteNombre = $request->filled('cliente_nombre') ? trim($request->input('cliente_nombre')) : null;
            $monto = $request->filled('monto') ? (float) $request->input('monto') : null;
            $montoLetras = ($monto && $monto > 0) ? $this->convertirMontoALetras($monto) : null;
            $concepto = $request->filled('concepto') ? trim($request->input('concepto')) : null;
        }

        $fecha = $request->filled('fecha') ? $request->input('fecha') : now()->format('Y-m-d');
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
            'fecha'           => $fecha,
            'motivo'          => $motivo,
            'user_id'         => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'recibo' => $recibo,
                'imprimir_url' => route('recibos_provisionales.imprimir', $recibo->id_recibo_provisional),
            ]);
        }

        return redirect()->route('recibos_provisionales.index')
            ->with('success', 'Recibo Provisional N° ' . ($recibo->numero_recibo_formateado) . ' generado exitosamente.')
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
            'leyendaPie'          => '⚠ RECIBO PROVISIONAL — Válido únicamente con sello y firma del cajero autorizado.',
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
}
