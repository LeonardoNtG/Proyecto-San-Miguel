<?php

use App\Models\Configuracion;

if (!function_exists('setting')) {
    /**
     * Obtiene o establece el valor de un parámetro del sistema para el proyecto activo.
     *
     * @param string $clave Clave del parámetro
     * @param mixed $default Valor predeterminado si no existe
     * @param int|null $lotificacionId ID de la lotificación (opcional, por defecto proyecto activo)
     * @return mixed
     */
    function setting(string $clave, mixed $default = null, ?int $lotificacionId = null): mixed
    {
        return Configuracion::get($clave, $default, $lotificacionId);
    }
}
