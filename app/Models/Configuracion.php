<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'lotificacion_id',
        'clave',
        'valor',
        'tipo',
        'grupo',
        'descripcion'
    ];

    public function lotificacion()
    {
        return $this->belongsTo(Lotificacion::class, 'lotificacion_id');
    }

    /**
     * Resuelve el ID de la lotificación activa actual si no se proporciona uno explícitamente.
     */
    public static function resolveLotificacionId(?int $lotificacionId = null): ?int
    {
        if ($lotificacionId) {
            return $lotificacionId;
        }

        if (session()->has('lotificacion_id')) {
            return (int) session('lotificacion_id');
        }

        try {
            $active = app(\App\Services\LotificacionService::class)->getActiveLotificacion();
            if ($active) {
                return (int) $active->id;
            }
        } catch (\Exception $e) {}

        // Fallback a la primera lotificación disponible
        $primera = Lotificacion::first();
        return $primera ? (int) $primera->id : null;
    }

    /**
     * Obtiene el valor de una configuración casteado a su tipo correspondiente.
     */
    public static function get(string $clave, mixed $default = null, ?int $lotificacionId = null): mixed
    {
        $lotId = self::resolveLotificacionId($lotificacionId);
        if (!$lotId) {
            return $default;
        }

        $cacheKey = "config_{$lotId}_{$clave}";

        return Cache::remember($cacheKey, 3600, function () use ($lotId, $clave, $default) {
            $config = self::where('lotificacion_id', $lotId)->where('clave', $clave)->first();
            if (!$config) {
                return $default;
            }

            return self::castValue($config->valor, $config->tipo);
        });
    }

    /**
     * Guarda o actualiza una configuración e invalida la caché.
     */
    public static function set(string $clave, mixed $valor, string $tipo = 'string', string $grupo = 'general', ?string $descripcion = null, ?int $lotificacionId = null): self
    {
        $lotId = self::resolveLotificacionId($lotificacionId);
        if (!$lotId) {
            throw new \Exception('No hay un proyecto/lotificación activa seleccionada.');
        }

        $valorString = is_bool($valor) ? ($valor ? '1' : '0') : (string) $valor;

        $config = self::updateOrCreate(
            ['lotificacion_id' => $lotId, 'clave' => $clave],
            [
                'valor' => $valorString,
                'tipo' => $tipo,
                'grupo' => $grupo,
                'descripcion' => $descripcion
            ]
        );

        Cache::forget("config_{$lotId}_{$clave}");

        return $config;
    }

    /**
     * Limpia la caché de configuraciones para una lotificación.
     */
    public static function clearCache(?int $lotificacionId = null): void
    {
        $lotId = self::resolveLotificacionId($lotificacionId);
        if ($lotId) {
            $claves = self::where('lotificacion_id', $lotId)->pluck('clave');
            foreach ($claves as $clave) {
                Cache::forget("config_{$lotId}_{$clave}");
            }
        }
    }

    /**
     * Castea el valor en base de datos a su tipo nativo de PHP.
     */
    public static function castValue(?string $valor, string $tipo): mixed
    {
        if (is_null($valor)) {
            return null;
        }

        return match ($tipo) {
            'boolean' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $valor,
            'decimal', 'float' => (float) $valor,
            default => $valor,
        };
    }

    /**
     * Define los parámetros iniciales por defecto para un proyecto.
     */
    public static function getParametrosDefinicion(): array
    {
        return [
            // 1. Cobranza y Moras
            'cobranza' => [
                'titulo' => 'Cobranza y Moras',
                'icono' => 'fas fa-hand-holding-usd',
                'parametros' => [
                    'cobrar_mora' => [
                        'clave' => 'cobrar_mora',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Cobro Automático de Mora',
                        'descripcion' => 'Aplica recargo por mora a las cuotas vencidas.',
                    ],
                    'inicio_mora' => [
                        'clave' => 'inicio_mora',
                        'tipo' => 'string',
                        'default' => 'vencimiento',
                        'options' => [
                            'vencimiento' => 'A partir de la fecha de vencimiento + Días de gracia',
                            'mes_vencido' => 'Al cumplirse 1 mes de vencida la cuota + Días de gracia',
                        ],
                        'label' => '¿Cuándo Inicia la Mora?',
                        'descripcion' => 'Define si el período de mora comienza inmediatamente tras vencer la cuota o después de un mes completo de atraso.',
                    ],
                    'dias_gracia_mora' => [
                        'clave' => 'dias_gracia_mora',
                        'tipo' => 'integer',
                        'default' => 5,
                        'label' => 'Días de Gracia para Mora',
                        'descripcion' => 'Días de tolerancia adicionales antes de empezar a cobrar el recargo por día.',
                    ],
                    'tipo_mora' => [
                        'clave' => 'tipo_mora',
                        'tipo' => 'string',
                        'default' => 'porcentaje',
                        'options' => [
                            'porcentaje' => 'Porcentaje sobre la Cuota Mensual (%)',
                            'fijo' => 'Monto Fijo en Dólares ($)',
                        ],
                        'label' => 'Modalidad de Recargo',
                        'descripcion' => 'Elige si la mora se calcula como un porcentaje (%) de la cuota o como un monto fijo ($).',
                    ],
                    'valor_mora' => [
                        'clave' => 'valor_mora',
                        'tipo' => 'decimal',
                        'default' => 5.00,
                        'label' => 'Valor de la Mora Mensual (% ó $)',
                        'descripcion' => 'Si es porcentaje ingresa el número (ej. 5 para 5%). El sistema calcula su equivalente diario dividiendo entre 30 días.',
                    ],
                    'permitir_abonos_parciales' => [
                        'clave' => 'permitir_abonos_parciales',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Permitir Abonos Parciales',
                        'descripcion' => 'Permite al cliente abonar montos menores al valor de una cuota completa.',
                    ],
                ]
            ],

            // 2. Control de Caja y Seguridad
            'caja' => [
                'titulo' => 'Control de Caja y Operaciones',
                'icono' => 'fas fa-cash-register',
                'parametros' => [
                    'exigir_caja_abierta' => [
                        'clave' => 'exigir_caja_abierta',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Exigir Turno de Caja Abierto',
                        'descripcion' => 'Restringe el registro de abonos y ventas si el usuario no ha abierto su turno de caja.',
                    ],
                    'auto_abrir_recibo' => [
                        'clave' => 'auto_abrir_recibo',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Auto-Abrir Recibo tras el Pago',
                        'descripcion' => 'Abre automáticamente la pestaña de impresión del recibo tras registrar un abono.',
                    ],
                    'comentarios_efectivo_obligatorio' => [
                        'clave' => 'comentarios_efectivo_obligatorio',
                        'tipo' => 'boolean',
                        'default' => false,
                        'label' => 'Comentarios Obligatorios en Efectivo',
                        'descripcion' => 'Exige ingresar un comentario u observación en pagos en efectivo.',
                    ],
                ]
            ],

            // 3. Ventas y Reservas
            'ventas' => [
                'titulo' => 'Ventas y Reservas',
                'icono' => 'fas fa-map-marked-alt',
                'parametros' => [
                    'dias_vigencia_reserva' => [
                        'clave' => 'dias_vigencia_reserva',
                        'tipo' => 'integer',
                        'default' => 15,
                        'label' => 'Días de Vigencia de Reserva',
                        'descripcion' => 'Plazo en días para que una reserva sea formalizada antes de que el lote quede disponible.',
                    ],
                    'porcentaje_minimo_prima' => [
                        'clave' => 'porcentaje_minimo_prima',
                        'tipo' => 'decimal',
                        'default' => 10.00,
                        'label' => 'Porcentaje Mínimo de Prima (%)',
                        'descripcion' => 'Porcentaje mínimo sugerido del valor base exigido como prima.',
                    ],
                    'plazo_maximo_meses' => [
                        'clave' => 'plazo_maximo_meses',
                        'tipo' => 'integer',
                        'default' => 84,
                        'label' => 'Plazo Máximo de Financiamiento (Meses)',
                        'descripcion' => 'Número máximo de cuotas mensuales permitidas al crear un contrato.',
                    ],
                ]
            ],

            // 4. Recibos y Documentos
            'recibos' => [
                'titulo' => 'Recibos y Talonarios',
                'icono' => 'fas fa-receipt',
                'parametros' => [
                    'tipo_numeracion_recibo' => [
                        'clave' => 'tipo_numeracion_recibo',
                        'tipo' => 'string',
                        'default' => 'proyecto_correlativo',
                        'options' => [
                            'proyecto_correlativo' => 'Talonario Independiente por Proyecto (1, 2, 3...) [Recomendado]',
                            'global' => 'Numeración Global del Sistema (ID de Base de Datos)',
                        ],
                        'label' => 'Modalidad de Numeración',
                        'descripcion' => 'Define si este proyecto tiene su propia serie independiente de recibos que arranca desde 1 (o número inicial) o si comparte el consecutivo global con los demás proyectos.',
                    ],
                    'prefijo_recibo' => [
                        'clave' => 'prefijo_recibo',
                        'tipo' => 'string',
                        'default' => '',
                        'label' => 'Prefijo del Recibo (Opcional)',
                        'descripcion' => 'Siglas o texto previo al número (ej: CSC-, LC-, SM-). Déjalo vacío si solo deseas el número limpio.',
                    ],
                    'numero_inicial_recibo' => [
                        'clave' => 'numero_inicial_recibo',
                        'tipo' => 'integer',
                        'default' => 1,
                        'label' => 'Número Inicial del Talonario',
                        'descripcion' => 'Número inicial para nuevos recibos de este proyecto (útil si ya tenías un talonario físico previo y deseas continuar la serie).',
                    ],
                    'longitud_digitos_recibo' => [
                        'clave' => 'longitud_digitos_recibo',
                        'tipo' => 'string',
                        'default' => '1',
                        'options' => [
                            '1' => 'Sin ceros a la izquierda (Ej: 15)',
                            '4' => '4 dígitos con ceros (Ej: 0015)',
                            '6' => '6 dígitos con ceros (Ej: 000015)',
                        ],
                        'label' => 'Formato de Dígitos / Ceros a la Izquierda',
                        'descripcion' => 'Define cómo se visualiza el número en el recibo impreso y en los reportes.',
                    ],
                    'imprimir_doble_recibo' => [
                        'clave' => 'imprimir_doble_recibo',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Formato Doble Vía (Cliente + Copia Inmobiliaria)',
                        'descripcion' => 'Imprime el recibo en 2 partes en una sola página tamaño carta. Si se desmarca, se imprimirá un solo recibo al 100% de la hoja.',
                    ],
                    'proporcion_recibo_doble' => [
                        'clave' => 'proporcion_recibo_doble',
                        'tipo' => 'string',
                        'default' => '50_50',
                        'options' => [
                            '50_50' => '50% Cliente / 50% Empresa (Simétrico exacto)',
                            '55_45' => '55% Cliente / 45% Empresa (Cliente ligeramente más ancho)',
                            '60_40' => '60% Cliente / 40% Empresa (Cliente amplio, empresa compacto)',
                            '65_35' => '65% Cliente / 35% Empresa (Cliente dominante, empresa tipo talón)',
                        ],
                        'label' => 'Proporción de Ancho en Recibo Doble',
                        'descripcion' => 'Define cuánto espacio relativo ocupa la copia del cliente (izquierda con QR) frente a la copia de la empresa (derecha).',
                    ],
                    'mostrar_qr_recibo' => [
                        'clave' => 'mostrar_qr_recibo',
                        'tipo' => 'boolean',
                        'default' => true,
                        'label' => 'Incluir Código QR de Estado de Cuenta',
                        'descripcion' => 'Imprime el código QR de acceso directo al portal web del cliente en el recibo.',
                    ],
                    'sufijo_moneda_letras' => [
                        'clave' => 'sufijo_moneda_letras',
                        'tipo' => 'string',
                        'default' => 'DÓLARES NETOS',
                        'label' => 'Sufijo de Moneda en Letras',
                        'descripcion' => 'Texto que se agrega al final de la cantidad en palabras (ej: DÓLARES NETOS, DÓLARES AMERICANOS, CÓRDOBAS NETOS).',
                    ],
                    'leyenda_pie_recibo' => [
                        'clave' => 'leyenda_pie_recibo',
                        'tipo' => 'text',
                        'default' => 'Conserve este comprobante como constancia legal de su pago.',
                        'label' => 'Leyenda Legal al Pie del Recibo',
                        'descripcion' => 'Texto personalizado que aparecerá en el pie de página de los recibos emitidos.',
                    ],
                    'nombre_administrador_aprobador' => [
                        'clave' => 'nombre_administrador_aprobador',
                        'tipo' => 'string',
                        'default' => 'Administración',
                        'label' => 'Nombre del Aprobador en Reportes',
                        'descripcion' => 'Nombre o cargo que aparecerá en los reportes de cierre de caja en la sección de firmas.',
                    ],
                ]
            ],
        ];
    }
}
