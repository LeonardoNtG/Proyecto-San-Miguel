<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuracion;
use App\Models\Lotificacion;
use App\Models\Auditoria;

class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Muestra la pantalla de gestión de parámetros por proyecto.
     */
    public function index(Request $request)
    {
        // 1. Obtener todas las lotificaciones
        $lotificaciones = Lotificacion::orderBy('nombre')->get();

        // 2. Determinar la lotificación a configurar
        $targetLotificacionId = (int) $request->input('lotificacion_id', Configuracion::resolveLotificacionId());
        
        $lotificacionActiva = Lotificacion::find($targetLotificacionId);
        if (!$lotificacionActiva && $lotificaciones->isNotEmpty()) {
            $lotificacionActiva = $lotificaciones->first();
            $targetLotificacionId = $lotificacionActiva->id;
        }

        // 3. Obtener la estructura de parámetros
        $grupos = Configuracion::getParametrosDefinicion();

        // 4. Obtener las configuraciones guardadas en BD para esta lotificación
        $configuracionesGuardadas = Configuracion::where('lotificacion_id', $targetLotificacionId)
            ->pluck('valor', 'clave')
            ->toArray();

        // 5. Fusionar con valores por defecto
        foreach ($grupos as $grupoKey => &$grupoData) {
            foreach ($grupoData['parametros'] as $clave => &$param) {
                if (array_key_exists($clave, $configuracionesGuardadas)) {
                    $param['valor_actual'] = Configuracion::castValue($configuracionesGuardadas[$clave], $param['tipo']);
                } else {
                    $param['valor_actual'] = $param['default'];
                }
            }
        }

        return view('configuracion.parametros', compact(
            'lotificaciones',
            'lotificacionActiva',
            'targetLotificacionId',
            'grupos'
        ));
    }

    /**
     * Guarda y actualiza los parámetros del proyecto seleccionado.
     */
    public function update(Request $request)
    {
        $request->validate([
            'lotificacion_id' => 'required|exists:lotificaciones,id',
        ]);

        $lotificacionId = (int) $request->input('lotificacion_id');
        $lotificacion = Lotificacion::findOrFail($lotificacionId);
        $definiciones = Configuracion::getParametrosDefinicion();

        $cambios = 0;

        foreach ($definiciones as $grupoKey => $grupoData) {
            foreach ($grupoData['parametros'] as $clave => $paramDef) {
                $tipo = $paramDef['tipo'];
                $descripcion = $paramDef['descripcion'];

                if ($tipo === 'boolean') {
                    // Si es switch, si viene en el request es true (1), si no viene es false (0)
                    $valor = $request->has($clave) ? '1' : '0';
                } else {
                    $valor = $request->input($clave, $paramDef['default']);
                }

                Configuracion::set(
                    clave: $clave,
                    valor: $valor,
                    tipo: $tipo,
                    grupo: $grupoKey,
                    descripcion: $descripcion,
                    lotificacionId: $lotificacionId
                );

                $cambios++;
            }
        }

        Auditoria::log(
            'Actualizó Parámetros del Sistema',
            'Configuracion',
            $lotificacionId,
            "Se actualizaron {$cambios} parámetros para el proyecto: {$lotificacion->nombre}"
        );

        return redirect()->route('configuracion.parametros.index', ['lotificacion_id' => $lotificacionId])
            ->with('success', "¡Parámetros actualizados correctamente para el proyecto \"{$lotificacion->nombre}\"!");
    }
}
