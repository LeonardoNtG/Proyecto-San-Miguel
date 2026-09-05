<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Abono;
use App\Models\Cuota;
use App\Models\Lote;
use App\Models\Bloque;
use App\Models\Lotificacion;

class ImportacionController extends Controller
{
    private const ESTADOS_CONTRATO  = ["Vigente", "Rescindido", "Finalizado"];
    private const TIPOS_PAGO        = ["Prima", "Cuota", "Abono Extraordinario", "Cancelación", "Cancelacion"];
    private const METODOS_PAGO      = ["Efectivo", "Transferencia Bancaria", "Depósito Bancario", "Deposito Bancario", "Cheque"];
    private const ESTADOS_LOTE      = ["Disponible", "Reservado", "Vendido"];

    public function index()
    {
        $lotificaciones = Lotificacion::all();
        return view("importacion.index", compact("lotificaciones"));
    }

    public function descargarPlantilla()
    {
        $xml = $this->generarXmlPlantilla();
        $nombre = "Plantilla_Importacion_Clientes.xls";
        
        return response($xml, 200, [
            "Content-Type"        => "application/vnd.ms-excel; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$nombre}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ]);
    }

    private function generarXmlPlantilla(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
'<?mso-application progid="Excel.Sheet"?>' . "\n" .
'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n" .
'          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n" .
'          xmlns:x="urn:schemas-microsoft-com:office:excel">' . "\n" .
'  <Styles>' . "\n" .
'    <Style ss:ID="Header">' . "\n" .
'      <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>' . "\n" .
'      <Interior ss:Color="#1A3A6B" ss:Pattern="Solid"/>' . "\n" .
'      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n" .
'      <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>' . "\n" .
'    </Style>' . "\n" .
'    <Style ss:ID="Requerido">' . "\n" .
'      <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>' . "\n" .
'      <Interior ss:Color="#C0392B" ss:Pattern="Solid"/>' . "\n" .
'      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n" .
'    </Style>' . "\n" .
'    <Style ss:ID="Opcional">' . "\n" .
'      <Font ss:Bold="1" ss:Color="#000000" ss:Size="10"/>' . "\n" .
'      <Interior ss:Color="#F0E68C" ss:Pattern="Solid"/>' . "\n" .
'      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n" .
'    </Style>' . "\n" .
'    <Style ss:ID="Dato">' . "\n" .
'      <Font ss:Size="10"/>' . "\n" .
'      <Alignment ss:Vertical="Center"/>' . "\n" .
'    </Style>' . "\n" .
'    <Style ss:ID="Titulo">' . "\n" .
'      <Font ss:Bold="1" ss:Size="14" ss:Color="#1A3A6B"/>' . "\n" .
'      <Alignment ss:Horizontal="Left"/>' . "\n" .
'    </Style>' . "\n" .
'  </Styles>' . "\n" .
'  <Worksheet ss:Name="CLIENTES_CONTRATOS">' . "\n" .
'    <Table ss:DefaultRowHeight="18">' . "\n" .
'      <Row ss:Height="30">' . "\n" .
'        <Cell ss:StyleID="Titulo" ss:MergeAcross="18"><Data ss:Type="String">HOJA 1 - CLIENTES Y CONTRATOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="25">' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">expediente_num</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombres_apellidos</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">identificacion</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">telefono</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">direccion</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">estado_civil</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">oficio</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">pv_num</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">fecha_venta</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">precio_final</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">plazo_meses</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">cuota_mensual</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">estado_contrato</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">prima_pagada</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">fecha_prima</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">beneficiario_final</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">nota_beneficiario</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="18">' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">EXP-0001</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">MARIA KARINA PEREZ LOPEZ</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">89095854</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">DE CLARO 2C AL SUR ESTE</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">SOLTERA</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">MAESTRA</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">PV-2024-001</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">30/08/2026</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9000.00</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">60</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Vigente</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">500.00</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">30/08/2026</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'    </Table>' . "\n" .
'  </Worksheet>' . "\n" .
'  <Worksheet ss:Name="HISTORIAL_PAGOS">' . "\n" .
'    <Table ss:DefaultRowHeight="18">' . "\n" .
'      <Row ss:Height="30">' . "\n" .
'        <Cell ss:StyleID="Titulo" ss:MergeAcross="9"><Data ss:Type="String">HOJA 2 - HISTORIAL DE PAGOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="25">' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">identificacion_cliente</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">fecha_pago</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">monto_abonado</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">tipo_pago</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">metodo_pago</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">referencia</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">cuenta_destino</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Opcional"><Data ss:Type="String">numero_recibo_original</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="18">' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">15/09/2026</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Cuota</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Efectivo</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">REC-001</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'    </Table>' . "\n" .
'  </Worksheet>' . "\n" .
'  <Worksheet ss:Name="CATALOGO_LOTES">' . "\n" .
'    <Table ss:DefaultRowHeight="18">' . "\n" .
'      <Row ss:Height="30">' . "\n" .
'        <Cell ss:StyleID="Titulo" ss:MergeAcross="4"><Data ss:Type="String">HOJA 3 - CATALOGO DE LOTES (Opcional) | Solo si hay lotes que no existen aun en el sistema</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="25">' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">area_metros</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">precio_base</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Requerido"><Data ss:Type="String">estado</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'      <Row ss:Height="18">' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">176.25</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9000.00</Data></Cell>' . "\n" .
'        <Cell ss:StyleID="Dato"><Data ss:Type="String">Vendido</Data></Cell>' . "\n" .
'      </Row>' . "\n" .
'    </Table>' . "\n" .
'  </Worksheet>' . "\n" .
'  <Worksheet ss:Name="INSTRUCCIONES">' . "\n" .
'    <Table ss:DefaultRowHeight="16">' . "\n" .
'      <Row ss:Height="30"><Cell ss:StyleID="Titulo" ss:MergeAcross="1"><Data ss:Type="String">GUIA DE IMPORTACION MASIVA DE CLIENTES</Data></Cell></Row>' . "\n" .
'      <Row ss:Height="12"><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Header"><Data ss:Type="String">HOJA</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">DESCRIPCION</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">CLIENTES_CONTRATOS</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Una fila por contrato. Si un cliente tiene 2 lotes, son 2 filas con la misma cedula.</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">HISTORIAL_PAGOS</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Un registro por cada abono o pago realizado historicamente.</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">CATALOGO_LOTES</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Opcional. Solo si los lotes aun no existen en el sistema.</Data></Cell></Row>' . "\n" .
'      <Row ss:Height="12"><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Header"><Data ss:Type="String">CAMPO</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">VALORES PERMITIDOS</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">estado_contrato</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Vigente | Rescindido | Finalizado</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">tipo_pago</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Prima | Cuota | Abono Extraordinario | Cancelacion</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">metodo_pago</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Efectivo | Transferencia Bancaria | Deposito Bancario | Cheque</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">estado (lotes)</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Disponible | Reservado | Vendido</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">fechas</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Formato DD/MM/AAAA Ejemplo: 30/08/2026</Data></Cell></Row>' . "\n" .
'      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">telefonos</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Guardar como TEXTO, no como numero, para preservar ceros iniciales</Data></Cell></Row>' . "\n" .
'    </Table>' . "\n" .
'  </Worksheet>' . "\n" .
'</Workbook>';
    }

    public function procesar(Request $request)
    {
        $request->validate([
            "archivo"         => "required|file|mimes:xlsx,xls,ods|max:20480",
            "lotificacion_id" => "required|exists:lotificaciones,id",
            "modo"            => "required|in:validar,importar",
        ], [
            "archivo.required"         => "Debe seleccionar un archivo Excel.",
            "archivo.mimes"            => "El archivo debe ser formato Excel (.xlsx o .xls).",
            "archivo.max"              => "El archivo no debe superar los 20 MB.",
            "lotificacion_id.required" => "Debe seleccionar el proyecto destino.",
        ]);

        $lotificacion = Lotificacion::findOrFail($request->lotificacion_id);
        $modo         = $request->modo;
        $archivo      = $request->file("archivo");

        try {
            $datos = $this->parsearExcel($archivo->getPathname());
        } catch (\Exception $e) {
            return back()->with("error", "No se pudo leer el archivo Excel: " . $e->getMessage());
        }

        $errores      = [];
        $advertencias = [];
        $resumen      = ["clientes_nuevos" => 0, "clientes_existentes" => 0, "contratos" => 0, "pagos" => 0, "lotes_creados" => 0];

        DB::beginTransaction();

        try {
            // FASE 0: Lotes opcionales
            if (!empty($datos["CATALOGO_LOTES"])) {
                [$errLotes, $advLotes, $resLotes] = $this->procesarLotes($datos["CATALOGO_LOTES"], $lotificacion, $modo);
                $errores      = array_merge($errores, $errLotes);
                $advertencias = array_merge($advertencias, $advLotes);
                $resumen["lotes_creados"] += $resLotes;
            }
            if (!empty($errores)) { DB::rollBack(); return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, false, $lotificacion->id); }

            // FASE 1: Clientes y Contratos
            if (empty($datos["CLIENTES_CONTRATOS"])) {
                DB::rollBack();
                return back()->with("error", "La hoja CLIENTES_CONTRATOS no fue encontrada o está vacía.");
            }
            [$errClientes, $advClientes, $resClientes, $mapeoVentas] = $this->procesarClientesContratos($datos["CLIENTES_CONTRATOS"], $lotificacion, $modo);
            $errores      = array_merge($errores, $errClientes);
            $advertencias = array_merge($advertencias, $advClientes);
            $resumen["clientes_nuevos"]     += $resClientes["clientes_nuevos"];
            $resumen["clientes_existentes"] += $resClientes["clientes_existentes"];
            $resumen["contratos"]           += $resClientes["contratos"];
            if (!empty($errores)) { DB::rollBack(); return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, false, $lotificacion->id); }

            // FASE 2: Historial de pagos
            if (!empty($datos["HISTORIAL_PAGOS"])) {
                [$errPagos, $advPagos, $numPagos] = $this->procesarPagos($datos["HISTORIAL_PAGOS"], $mapeoVentas, $lotificacion, $modo);
                $errores      = array_merge($errores, $errPagos);
                $advertencias = array_merge($advertencias, $advPagos);
                $resumen["pagos"] += $numPagos;
            }
            if (!empty($errores)) { DB::rollBack(); return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, false, $lotificacion->id); }

            if ($modo === "importar") { DB::commit(); } else { DB::rollBack(); }
            return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, true, $lotificacion->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Importación masiva falló: " . $e->getMessage());
            return back()->with("error", "Error inesperado: " . $e->getMessage());
        }
    }

    private function procesarLotes(array $filas, Lotificacion $lotificacion, string $modo): array
    {
        $errores = []; $advertencias = []; $creados = 0;
        foreach ($filas as $i => $fila) {
            $numFila     = $i + 2;
            $nombreBloque = trim($fila["nombre_bloque"] ?? "");
            $numeroLote  = trim($fila["numero_lote"] ?? "");
            $areaMetros  = $fila["area_metros"] ?? null;
            $precioBase  = $fila["precio_base"] ?? null;
            $estado      = trim($fila["estado"] ?? "Disponible");

            if (empty($nombreBloque)) { $errores[] = "[Lotes F{$numFila}] nombre_bloque obligatorio."; continue; }
            if (empty($numeroLote))   { $errores[] = "[Lotes F{$numFila}] numero_lote obligatorio."; continue; }
            if (!is_numeric($areaMetros) || $areaMetros <= 0) { $errores[] = "[Lotes F{$numFila}] area_metros inválido: {$areaMetros}"; continue; }
            if (!is_numeric($precioBase) || $precioBase < 0)  { $errores[] = "[Lotes F{$numFila}] precio_base inválido."; continue; }
            if (!in_array($estado, self::ESTADOS_LOTE)) { $errores[] = "[Lotes F{$numFila}] estado inválido: {$estado}"; continue; }

            $bloque = Bloque::withoutGlobalScope("lotificacion")->where("nombre", $nombreBloque)->where("lotificacion_id", $lotificacion->id)->first();
            if (!$bloque) { $errores[] = "[Lotes F{$numFila}] Bloque '{$nombreBloque}' no encontrado."; continue; }

            $existe = Lote::withoutGlobalScope("lotificacion")->where("id_bloque", $bloque->id_bloque)->where("numero_lote", $numeroLote)->exists();
            if ($existe) { $advertencias[] = "[Lotes F{$numFila}] Lote {$numeroLote}/{$nombreBloque} ya existe. Omitido."; continue; }

            if ($modo === "importar") {
                Lote::create(["id_bloque" => $bloque->id_bloque, "numero_lote" => $numeroLote, "area_metros" => (float)$areaMetros, "precio_base" => (float)$precioBase, "estado" => $estado]);
            }
            $creados++;
        }
        return [$errores, $advertencias, $creados];
    }

    private function procesarClientesContratos(array $filas, Lotificacion $lotificacion, string $modo): array
    {
        $errores = []; $advertencias = []; $resumen = ["clientes_nuevos" => 0, "clientes_existentes" => 0, "contratos" => 0];
        $mapeoVentas = []; $clientesCache = [];

        foreach ($filas as $i => $fila) {
            $numFila        = $i + 2;
            $expediente     = mb_strtoupper(trim($fila["expediente_num"] ?? ""), 'UTF-8');
            $nombres        = mb_strtoupper(trim($fila["nombres_apellidos"] ?? ""), 'UTF-8');
            $identificacionRaw = trim($fila["identificacion"] ?? "");
            $cleanId = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $identificacionRaw));
            if (strlen($cleanId) === 14) {
                $identificacion = substr($cleanId, 0, 3) . '-' . substr($cleanId, 3, 6) . '-' . substr($cleanId, 9, 5);
            } else {
                $identificacion = mb_strtoupper($identificacionRaw, 'UTF-8');
            }
            $telefono       = trim($fila["telefono"] ?? "");
            $direccion      = !empty(trim($fila["direccion"] ?? "")) ? mb_strtoupper(trim($fila["direccion"]), 'UTF-8') : null;
            $estadoCivil    = !empty(trim($fila["estado_civil"] ?? "")) ? mb_strtoupper(trim($fila["estado_civil"]), 'UTF-8') : null;
            $oficio         = !empty(trim($fila["oficio"] ?? "")) ? mb_strtoupper(trim($fila["oficio"]), 'UTF-8') : null;
            $pvNum          = mb_strtoupper(trim($fila["pv_num"] ?? ""), 'UTF-8');
            $nombreBloque   = trim($fila["nombre_bloque"] ?? "");
            $numeroLote     = trim($fila["numero_lote"] ?? "");
            $fechaVenta     = trim($fila["fecha_venta"] ?? "");
            $precioFinal    = $fila["precio_final"] ?? null;
            $plazoMeses     = $fila["plazo_meses"] ?? null;
            $cuotaMensual   = $fila["cuota_mensual"] ?? null;
            $estadoContrato = trim($fila["estado_contrato"] ?? "Vigente");
            $primaPagada    = $fila["prima_pagada"] ?? null;
            $fechaPrima     = trim($fila["fecha_prima"] ?? "");
            $beneficiario   = !empty(trim($fila["beneficiario_final"] ?? "")) ? mb_strtoupper(trim($fila["beneficiario_final"]), 'UTF-8') : null;
            $notaBenef      = !empty(trim($fila["nota_beneficiario"] ?? "")) ? mb_strtoupper(trim($fila["nota_beneficiario"]), 'UTF-8') : null;

            // Validaciones obligatorias
            $camposObligatorios = [
                "expediente_num"    => $expediente,
                "nombres_apellidos" => $nombres,
                "identificacion"    => $identificacion,
                "nombre_bloque"     => $nombreBloque,
                "numero_lote"       => $numeroLote,
            ];
            $faltantes = array_keys(array_filter($camposObligatorios, fn($v) => $v === ""));
            if (!empty($faltantes)) {
                $errores[] = "[Contratos F{$numFila}] Campos obligatorios vacíos: " . implode(", ", $faltantes);
                continue;
            }
            if (!in_array($estadoContrato, self::ESTADOS_CONTRATO)) {
                $errores[] = "[Contratos F{$numFila}] estado_contrato inválido: '{$estadoContrato}'.";
                continue;
            }
            $fechaVentaParsed = $this->parsearFecha($fechaVenta);
            if (!$fechaVentaParsed) {
                $errores[] = "[Contratos F{$numFila}] fecha_venta inválida: '{$fechaVenta}'. Use DD/MM/AAAA.";
                continue;
            }
            if (!is_numeric($precioFinal) || (float)$precioFinal < 0) {
                $errores[] = "[Contratos F{$numFila}] precio_final inválido: '{$precioFinal}'";
                continue;
            }
            if (!is_numeric($plazoMeses) || (int)$plazoMeses < 0) {
                $errores[] = "[Contratos F{$numFila}] plazo_meses inválido: '{$plazoMeses}'";
                continue;
            }
            if (!is_numeric($cuotaMensual) || (float)$cuotaMensual < 0) {
                $errores[] = "[Contratos F{$numFila}] cuota_mensual inválido: '{$cuotaMensual}'";
                continue;
            }

            // Advertencia de consistencia financiera
            $plazoInt  = (int)$plazoMeses;
            $precioNum = (float)$precioFinal;
            $cuotaNum  = (float)$cuotaMensual;
            if ($plazoInt > 0 && $cuotaNum > 0) {
                $total = round($plazoInt * $cuotaNum, 2);
                $diff  = abs($total - $precioNum);
                if ($diff > 1.00) {
                    $advertencias[] = "[Contratos F{$numFila}] plazo x cuota=\${$total} difiere del precio_final=\${$precioNum} (diferencia: \${$diff}).";
                }
            }

            // Expediente duplicado
            if (Cliente::withoutGlobalScope("lotificacion")->where("expediente_num", $expediente)->exists()) {
                $errores[] = "[Contratos F{$numFila}] Expediente '{$expediente}' ya existe.";
                continue;
            }

            // Buscar o crear cliente
            if (isset($clientesCache[$identificacion])) {
                $cliente = $clientesCache[$identificacion];
                $resumen["clientes_existentes"]++;
            } else {
                $clienteExistente = Cliente::withoutGlobalScope("lotificacion")->where("identificacion", $identificacion)->first();
                if ($clienteExistente) {
                    $cliente = $clienteExistente;
                    $resumen["clientes_existentes"]++;
                } else {
                    if ($modo === "importar") {
                        $cliente = Cliente::create([
                            "expediente_num"    => $expediente,
                            "nombres_apellidos" => $nombres,
                            "identificacion"    => $identificacion,
                            "telefono"          => $telefono ?: null,
                            "direccion"         => $direccion ?: null,
                            "estado_civil"      => $estadoCivil ?: null,
                            "oficio"            => $oficio ?: null,
                            "pv_num"            => $pvNum ?: null,
                            "token_seguimiento" => Str::uuid()->toString(),
                        ]);
                    } else {
                        $cliente = (object)["id_cliente" => "SIM_{$identificacion}", "identificacion" => $identificacion];
                    }
                    $resumen["clientes_nuevos"]++;
                }
                $clientesCache[$identificacion] = $cliente;
            }

            // Buscar bloque y lote
            $bloque = Bloque::withoutGlobalScope("lotificacion")->where("nombre", $nombreBloque)->where("lotificacion_id", $lotificacion->id)->first();
            if (!$bloque) {
                $errores[] = "[Contratos F{$numFila}] Bloque '{$nombreBloque}' no encontrado en proyecto '{$lotificacion->nombre}'.";
                continue;
            }
            $lote = Lote::withoutGlobalScope("lotificacion")->where("id_bloque", $bloque->id_bloque)->where("numero_lote", $numeroLote)->first();
            if (!$lote) {
                $errores[] = "[Contratos F{$numFila}] Lote '{$numeroLote}' no encontrado en bloque '{$nombreBloque}'.";
                continue;
            }

            // Verificar lote sin contrato vigente
            $ventaVigente = Venta::withoutGlobalScope("lotificacion")->where("id_lote", $lote->id_lote)->where("estado_contrato", "Vigente")->first();
            if ($ventaVigente) {
                $errores[] = "[Contratos F{$numFila}] Lote '{$numeroLote}'/'{$nombreBloque}' ya tiene contrato Vigente (ID: {$ventaVigente->id_venta}).";
                continue;
            }

            // Crear Venta
            $ventaId = null;
            if ($modo === "importar") {
                $venta = Venta::create([
                    "id_cliente"         => $cliente->id_cliente,
                    "id_lote"            => $lote->id_lote,
                    "lotificacion_id"    => $lotificacion->id,
                    "fecha_venta"        => $fechaVentaParsed,
                    "precio_final"       => $precioNum,
                    "plazo_meses"        => $plazoInt,
                    "cuota_mensual"      => $cuotaNum,
                    "extension_lote"     => $lote->area_metros . " m²",
                    "estado_contrato"    => $estadoContrato,
                    "beneficiario_final" => $beneficiario ?: null,
                    "nota_beneficiario"  => $notaBenef ?: null,
                ]);
                $ventaId = $venta->id_venta;
                $lote->estado = ($estadoContrato === "Rescindido") ? "Disponible" : "Vendido";
                $lote->save();
                if ($estadoContrato === "Vigente" && $plazoInt > 0) {
                    $this->generarPlanCuotas($venta, $fechaVentaParsed);
                }
                // Prima como abono
                if (!empty($primaPagada) && is_numeric($primaPagada) && (float)$primaPagada > 0) {
                    $fechaPrimaParsed = $this->parsearFecha($fechaPrima) ?? $fechaVentaParsed;
                    $maxNum = (int)(Abono::withoutGlobalScope("lotificacion")->whereHas("venta", fn($q) => $q->where("lotificacion_id", $lotificacion->id))->max("numero_recibo") ?? 0);
                    $maxNum++;
                    Abono::create([
                        "id_venta" => $ventaId, "fecha_pago" => $fechaPrimaParsed,
                        "monto_abonado" => (float)$primaPagada, "tipo_pago" => "Prima",
                        "metodo_pago" => "Efectivo", "numero_recibo" => $maxNum, "codigo_recibo" => (string)$maxNum,
                    ]);
                    $resumen["pagos"]++;
                }
            }

            $clave = strtolower("{$identificacion}_{$nombreBloque}_{$numeroLote}");
            $mapeoVentas[$clave] = $ventaId;
            $resumen["contratos"]++;
        }

        return [$errores, $advertencias, $resumen, $mapeoVentas];
    }

    private function procesarPagos(array $filas, array $mapeoVentas, Lotificacion $lotificacion, string $modo): array
    {
        $errores = []; $advertencias = []; $procesados = 0;
        $numRecibo = (int)(Abono::withoutGlobalScope("lotificacion")->whereHas("venta", fn($q) => $q->where("lotificacion_id", $lotificacion->id))->max("numero_recibo") ?? 0);

        foreach ($filas as $i => $fila) {
            $numFila        = $i + 2;
            $identificacion = trim($fila["identificacion_cliente"] ?? "");
            $numeroLote     = trim($fila["numero_lote"] ?? "");
            $nombreBloque   = trim($fila["nombre_bloque"] ?? "");
            $fechaPago      = trim($fila["fecha_pago"] ?? "");
            $monto          = $fila["monto_abonado"] ?? null;
            $tipoPago       = trim($fila["tipo_pago"] ?? "Cuota");
            $metodoPago     = trim($fila["metodo_pago"] ?? "Efectivo");
            $referencia     = trim($fila["referencia"] ?? "");
            $cuentaDestino  = trim($fila["cuenta_destino"] ?? "");
            $numOrig        = trim($fila["numero_recibo_original"] ?? "");

            if (empty($identificacion) || empty($numeroLote) || empty($nombreBloque)) {
                $advertencias[] = "[Pagos F{$numFila}] Campos de identificación incompletos. Fila omitida.";
                continue;
            }
            $fechaParsed = $this->parsearFecha($fechaPago);
            if (!$fechaParsed) { $errores[] = "[Pagos F{$numFila}] fecha_pago inválida: '{$fechaPago}'."; continue; }
            if (!is_numeric($monto) || (float)$monto <= 0) { $errores[] = "[Pagos F{$numFila}] monto_abonado inválido: '{$monto}'"; continue; }
            if (!in_array($tipoPago, self::TIPOS_PAGO)) { $advertencias[] = "[Pagos F{$numFila}] tipo_pago '{$tipoPago}' desconocido. Se usará 'Cuota'."; $tipoPago = "Cuota"; }
            if (!in_array($metodoPago, self::METODOS_PAGO)) { $advertencias[] = "[Pagos F{$numFila}] metodo_pago '{$metodoPago}' desconocido. Se usará 'Efectivo'."; $metodoPago = "Efectivo"; }

            $clave = strtolower("{$identificacion}_{$nombreBloque}_{$numeroLote}");
            if (!isset($mapeoVentas[$clave])) {
                $advertencias[] = "[Pagos F{$numFila}] Sin contrato para cédula='{$identificacion}', Bloque='{$nombreBloque}', Lote='{$numeroLote}'. Omitido.";
                continue;
            }

            $idVenta = $mapeoVentas[$clave];
            if ($modo === "importar" && $idVenta) {
                $numRecibo++;
                Abono::create([
                    "id_venta"       => $idVenta,
                    "fecha_pago"     => $fechaParsed,
                    "monto_abonado"  => (float)$monto,
                    "tipo_pago"      => $tipoPago,
                    "metodo_pago"    => $metodoPago,
                    "referencia"     => $referencia ?: null,
                    "cuenta_destino" => $cuentaDestino ?: null,
                    "numero_recibo"  => $numRecibo,
                    "codigo_recibo"  => !empty($numOrig) ? $numOrig : (string)$numRecibo,
                ]);
            }
            $procesados++;
        }
        return [$errores, $advertencias, $procesados];
    }

    private function generarPlanCuotas(Venta $venta, string $fechaInicio): void
    {
        $plazo = $venta->plazo_meses;
        $cuota = $venta->cuota_mensual;
        $saldo = $venta->precio_final;
        $fecha = Carbon::parse($fechaInicio);
        for ($i = 1; $i <= $plazo; $i++) {
            $fecha->addMonth();
            $montoCuota = ($i === $plazo) ? $saldo : $cuota;
            Cuota::create(["id_venta" => $venta->id_venta, "numero_cuota" => $i, "fecha_vencimiento" => $fecha->format("Y-m-d"), "monto_total" => $montoCuota, "capital" => $montoCuota, "interes" => 0, "saldo_restante" => $montoCuota, "estado" => "Pendiente"]);
            $saldo -= $montoCuota;
        }
    }

    private function parsearExcel(string $ruta): array
    {
        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        if ($ext === "xls") {
            throw new \Exception("El formato .xls (Excel 97-2003) no es compatible. Guarde como .xlsx y reintente.");
        }
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new \Exception("No se pudo abrir el archivo XLSX. Verifique que no esté dañado.");
        }

        // Shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName("xl/sharedStrings.xml");
        if ($ssXml) {
            $ss = simplexml_load_string($ssXml);
            foreach ($ss->si as $si) {
                $txt = "";
                foreach ($si->r as $r) { $txt .= (string)$r->t; }
                if ($txt === "") { $txt = (string)$si->t; }
                $sharedStrings[] = $txt;
            }
        }

        // Workbook + rels
        $wbXml   = $zip->getFromName("xl/workbook.xml");
        $wb      = simplexml_load_string($wbXml);
        $relsXml = $zip->getFromName("xl/_rels/workbook.xml.rels");
        $rels    = simplexml_load_string($relsXml);
        $sheetRels = [];
        foreach ($rels->Relationship as $rel) {
            $sheetRels[(string)$rel["Id"]] = (string)$rel["Target"];
        }

        $stylesXml    = $zip->getFromName("xl/styles.xml");
        $dateStyleIds = $this->obtenerEstilosFecha($stylesXml);

        $resultado = [];
        foreach ($wb->sheets->sheet as $sheet) {
            $nombre = (string)$sheet["name"];
            $rId    = (string)$sheet->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships")["id"];
            $target = $sheetRels[$rId] ?? null;
            if (!$target) continue;
            $sheetPath = "xl/" . ltrim($target, "/");
            $sheetXml  = $zip->getFromName($sheetPath);
            if (!$sheetXml) continue;
            $resultado[$nombre] = $this->parsearHoja($sheetXml, $sharedStrings, $dateStyleIds);
        }
        $zip->close();
        return $resultado;
    }

    private function parsearHoja(string $xml, array $sharedStrings, array $dateStyleIds): array
    {
        $sheet  = simplexml_load_string($xml);
        $filas  = []; $header = []; $rowIdx = 0;
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $col      = preg_replace("/[0-9]/", "", (string)$cell["r"]);
                $type     = (string)$cell["t"];
                $styleId  = (int)($cell["s"] ?? -1);
                $raw      = (string)($cell->v ?? "");
                if ($type === "s") {
                    $valor = $sharedStrings[(int)$raw] ?? "";
                } elseif ($type === "b") {
                    $valor = $raw === "1" ? "true" : "false";
                } elseif (in_array($styleId, $dateStyleIds) && is_numeric($raw) && $raw !== "") {
                    $valor = $this->excelSerialToDate($raw);
                } else {
                    $valor = $raw;
                }
                $rowData[$col] = $valor;
            }
            if ($rowIdx === 0) {
                foreach ($rowData as $col => $enc) {
                    $header[$col] = strtolower(str_replace([" ", "-"], "_", trim($enc)));
                }
            } else {
                if (!empty(array_filter($rowData, fn($v) => $v !== ""))) {
                    $filaMapeada = [];
                    foreach ($header as $col => $campo) { $filaMapeada[$campo] = $rowData[$col] ?? ""; }
                    $filas[] = $filaMapeada;
                }
            }
            $rowIdx++;
        }
        return $filas;
    }

    private function obtenerEstilosFecha(?string $xml): array
    {
        if (!$xml) return [];
        $ids = []; $styles = simplexml_load_string($xml);
        if (!$styles) return [];
        $fechaBuiltin = array_merge(range(14, 17), [22]);
        $xfIdx = 0;
        foreach ($styles->cellXfs->xf ?? [] as $xf) {
            if (in_array((int)$xf["numFmtId"], $fechaBuiltin)) { $ids[] = $xfIdx; }
            $xfIdx++;
        }
        return $ids;
    }

    private function excelSerialToDate(string $serial): string
    {
        return Carbon::createFromDate(1899, 12, 30)->addDays((int)$serial)->format("d/m/Y");
    }

    private function parsearFecha(string $valor): ?string
    {
        if (empty($valor)) return null;
        foreach (["d/m/Y", "Y-m-d", "d-m-Y", "m/d/Y", "d/m/y", "Y/m/d"] as $fmt) {
            try {
                $f = Carbon::createFromFormat($fmt, $valor);
                if ($f && $f->year > 1900 && $f->year < 2100) return $f->format("Y-m-d");
            } catch (\Exception $e) {}
        }
        try { $f = Carbon::parse($valor); if ($f->year > 1900 && $f->year < 2100) return $f->format("Y-m-d"); } catch (\Exception $e) {}
        return null;
    }

    private function respuestaResultado(array $errores, array $advertencias, array $resumen, string $modo, bool $exitoso, $lotificacionId = null)
    {
        session([
            "import_errores"          => $errores,
            "import_advertencias"     => $advertencias,
            "import_resumen"          => $resumen,
            "import_modo"             => $modo,
            "import_exitoso"          => $exitoso,
            "import_lotificacion_id"  => $lotificacionId,
        ]);
        return redirect()->route("importacion.index")->with("show_result", true);
    }
}
