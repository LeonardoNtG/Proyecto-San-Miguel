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
        $content = $this->generarXlsxPlantilla();
        $nombre  = "Plantilla_Importacion_Clientes.xlsx";

        return response($content, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=\"{$nombre}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ]);
    }

    /**
     * Genera un archivo .xlsx real (OOXML/ZIP) listo para abrir en Excel sin conversión.
     * Usa inline strings para evitar la necesidad de sharedStrings.xml.
     */
    private function generarXlsxPlantilla(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'amsa_plantilla_');

        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::OVERWRITE);

        // ── [Content_Types].xml ──────────────────────────────────────────────────
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
              '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
              '<Default Extension="xml" ContentType="application/xml"/>' .
              '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
              '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
              '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
              '<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
              '<Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
              '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>');

        // ── _rels/.rels ──────────────────────────────────────────────────────────
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
              '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');

        // ── xl/workbook.xml ──────────────────────────────────────────────────────
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
              '<sheets>' .
                '<sheet name="CLIENTES_CONTRATOS" sheetId="1" r:id="rId1"/>' .
                '<sheet name="HISTORIAL_PAGOS"    sheetId="2" r:id="rId2"/>' .
                '<sheet name="CATALOGO_LOTES"     sheetId="3" r:id="rId3"/>' .
                '<sheet name="INSTRUCCIONES"      sheetId="4" r:id="rId4"/>' .
              '</sheets>' .
            '</workbook>');

        // ── xl/_rels/workbook.xml.rels ────────────────────────────────────────────
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
              '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
              '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' .
              '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>' .
              '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>' .
              '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"    Target="styles.xml"/>' .
            '</Relationships>');

        // ── xl/styles.xml ─────────────────────────────────────────────────────────
        // Style index: 0=Normal  1=Header(azul oscuro)  2=Requerido(rojo)  3=Opcional(amarillo)  4=Titulo(azul grande)
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
              '<fonts count="5">' .
                '<font><sz val="10"/><color rgb="FF000000"/><name val="Calibri"/></font>' .
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' .
                '<font><b/><sz val="10"/><color rgb="FF000000"/><name val="Calibri"/></font>' .
                '<font><b/><sz val="14"/><color rgb="FF1A3A6B"/><name val="Calibri"/></font>' .
                '<font><sz val="10"/><color rgb="FF000000"/><name val="Calibri"/></font>' .
              '</fonts>' .
              '<fills count="6">' .
                '<fill><patternFill patternType="none"/></fill>' .
                '<fill><patternFill patternType="gray125"/></fill>' .
                '<fill><patternFill patternType="solid"><fgColor rgb="FF1A3A6B"/></patternFill></fill>' .
                '<fill><patternFill patternType="solid"><fgColor rgb="FFC0392B"/></patternFill></fill>' .
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF0E68C"/></patternFill></fill>' .
                '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>' .
              '</fills>' .
              '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' .
              '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
              '<cellXfs count="5">' .
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
                '<xf numFmtId="0" fontId="1" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' .
                '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>' .
              '</cellXfs>' .
            '</styleSheet>');

        // ── Helper: celda de texto inline ─────────────────────────────────────────
        $s = function(string $ref, string $text, int $style = 0): string {
            $safe = htmlspecialchars($text, ENT_XML1, 'UTF-8');
            return "<c r=\"{$ref}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$safe}</t></is></c>";
        };
        $n = function(string $ref, $val, int $style = 0): string {
            return "<c r=\"{$ref}\" s=\"{$style}\"><v>{$val}</v></c>";
        };

        // ── Hoja 1: CLIENTES_CONTRATOS ────────────────────────────────────────────
        $zip->addFromString('xl/worksheets/sheet1.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' .
              '<row r="1" ht="36" customHeight="1">' .
                $s('A1', 'HOJA 1 - CLIENTES Y CONTRATOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales', 4) .
              '</row>' .
              '<row r="2" ht="28" customHeight="1">' .
                $s('A2','expediente_num',2)    . $s('B2','nombres_apellidos',2) . $s('C2','identificacion',2) .
                $s('D2','telefono',3)          . $s('E2','direccion',3)         . $s('F2','estado_civil',3) .
                $s('G2','oficio',3)            . $s('H2','pv_num',3) .
                $s('I2','nombre_bloque',2)     . $s('J2','numero_lote',2)       . $s('K2','fecha_venta',2) .
                $s('L2','precio_final',2)      . $s('M2','plazo_meses',2)       . $s('N2','cuota_mensual',2) .
                $s('O2','estado_contrato',2)   .
                $s('P2','prima_pagada',3)      . $s('Q2','fecha_prima',3) .
                $s('R2','beneficiario_final',3). $s('S2','nota_beneficiario',3) .
              '</row>' .
              '<row r="3">' .
                $s('A3','EXP-0001')            . $s('B3','MARIA KARINA PEREZ LOPEZ') . $s('C3','001-230489-0001X') .
                $s('D3','89095854')            . $s('E3','DE CLARO 2C AL SUR ESTE')  . $s('F3','SOLTERA') .
                $s('G3','MAESTRA')             . $s('H3','PV-2024-001') .
                $s('I3','Bloque A')            . $s('J3','A-01')                     . $s('K3','30/08/2026') .
                $n('L3', 9000)                 . $n('M3', 60)                         . $n('N3', 150) .
                $s('O3','Vigente') .
                $n('P3', 500)                  . $s('Q3','30/08/2026') .
                $s('R3','')                    . $s('S3','') .
              '</row>' .
            '</sheetData>' .
            '</worksheet>');

        // ── Hoja 2: HISTORIAL_PAGOS ───────────────────────────────────────────────
        $zip->addFromString('xl/worksheets/sheet2.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' .
              '<row r="1" ht="36" customHeight="1">' .
                $s('A1', 'HOJA 2 - HISTORIAL DE PAGOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales', 4) .
              '</row>' .
              '<row r="2" ht="28" customHeight="1">' .
                $s('A2','identificacion_cliente',2) . $s('B2','numero_lote',2)   . $s('C2','nombre_bloque',2) .
                $s('D2','fecha_pago',2)             . $s('E2','monto_abonado',2) . $s('F2','tipo_pago',2) .
                $s('G2','metodo_pago',3)            . $s('H2','referencia',3)    . $s('I2','cuenta_destino',3) .
                $s('J2','numero_recibo_original',3) .
              '</row>' .
              '<row r="3">' .
                $s('A3','001-230489-0001X') . $s('B3','A-01')      . $s('C3','Bloque A') .
                $s('D3','15/09/2026')       . $n('E3', 150)         . $s('F3','Cuota') .
                $s('G3','Efectivo')         . $s('H3','')           . $s('I3','') .
                $s('J3','REC-001') .
              '</row>' .
            '</sheetData>' .
            '</worksheet>');

        // ── Hoja 3: CATALOGO_LOTES ────────────────────────────────────────────────
        $zip->addFromString('xl/worksheets/sheet3.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' .
              '<row r="1" ht="36" customHeight="1">' .
                $s('A1', 'HOJA 3 - CATALOGO DE LOTES (Opcional) | Solo si hay lotes que no existen aun en el sistema', 4) .
              '</row>' .
              '<row r="2" ht="28" customHeight="1">' .
                $s('A2','nombre_bloque',2) . $s('B2','numero_lote',2) . $s('C2','area_metros',2) .
                $s('D2','precio_base',2)   . $s('E2','estado',2) .
              '</row>' .
              '<row r="3">' .
                $s('A3','Bloque A') . $s('B3','A-01') . $n('C3', 176.25) . $n('D3', 9000) . $s('E3','Vendido') .
              '</row>' .
            '</sheetData>' .
            '</worksheet>');

        // ── Hoja 4: INSTRUCCIONES ─────────────────────────────────────────────────
        $zip->addFromString('xl/worksheets/sheet4.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' .
              '<row r="1" ht="36" customHeight="1">' . $s('A1','GUIA DE IMPORTACION MASIVA DE CLIENTES',4) . '</row>' .
              '<row r="2"><' . 'c r="A2" t="inlineStr" s="0"><is><t></t></is></c></row>' .
              '<row r="3">' . $s('A3','HOJA',1) . $s('B3','DESCRIPCION',1) . '</row>' .
              '<row r="4">' . $s('A4','CLIENTES_CONTRATOS') . $s('B4','Una fila por contrato. Si un cliente tiene 2 lotes, son 2 filas con la misma identificacion.') . '</row>' .
              '<row r="5">' . $s('A5','HISTORIAL_PAGOS')    . $s('B5','Un registro por cada abono o pago realizado historicamente.') . '</row>' .
              '<row r="6">' . $s('A6','CATALOGO_LOTES')     . $s('B6','Opcional. Solo si los lotes aun no existen en el sistema.') . '</row>' .
              '<row r="7"><' . 'c r="A7" t="inlineStr" s="0"><is><t></t></is></c></row>' .
              '<row r="8">'  . $s('A8','CAMPO',1) . $s('B8','VALORES PERMITIDOS',1) . '</row>' .
              '<row r="9">'  . $s('A9', 'estado_contrato') . $s('B9', 'Vigente | Rescindido | Finalizado') . '</row>' .
              '<row r="10">' . $s('A10','tipo_pago')        . $s('B10','Prima | Cuota | Abono Extraordinario | Cancelacion') . '</row>' .
              '<row r="11">' . $s('A11','metodo_pago')      . $s('B11','Efectivo | Transferencia Bancaria | Deposito Bancario | Cheque') . '</row>' .
              '<row r="12">' . $s('A12','estado (lotes)')   . $s('B12','Disponible | Reservado | Vendido') . '</row>' .
              '<row r="13">' . $s('A13','fechas')           . $s('B13','Formato DD/MM/AAAA Ejemplo: 30/08/2026') . '</row>' .
              '<row r="14">' . $s('A14','telefonos')        . $s('B14','Guardar como TEXTO para preservar ceros iniciales. Ejemplo: 08912345') . '</row>' .
            '</sheetData>' .
            '</worksheet>');

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /** @deprecated Reemplazado por generarXlsxPlantilla() - Este método ya no se usa */
    private function generarXmlPlantilla_obsoleto(): string
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
            "archivo"         => "required|file|max:20480",
            "lotificacion_id" => "required|exists:lotificaciones,id",
            "modo"            => "required|in:validar,importar",
        ], [
            "archivo.required"         => "Debe seleccionar un archivo Excel.",
            "archivo.max"              => "El archivo no debe superar los 20 MB.",
            "lotificacion_id.required" => "Debe seleccionar el proyecto destino.",
        ]);

        $lotificacion = Lotificacion::findOrFail($request->lotificacion_id);
        $modo         = $request->modo;
        $archivo      = $request->file("archivo");

        try {
            $datos = $this->parsearExcel($archivo->getPathname(), $archivo->getClientOriginalName());
        } catch (\Exception $e) {
            return back()->with("error", "No se pudo leer el archivo Excel: " . $e->getMessage());
        }

        $errores      = [];
        $advertencias = [];
        $resumen      = ["clientes_nuevos" => 0, "clientes_existentes" => 0, "contratos" => 0, "pagos" => 0, "lotes_creados" => 0];

        // Obtener hojas de forma flexible por nombre o posición
        $hojaLotes     = $this->buscarHoja($datos, ['CATALOGO_LOTES', 'CATALOGO LOTES', 'LOTES', 'CATALOGO_DE_LOTES', 'CATALOGO', 'HOJA 3', 'HOJA3']);
        $hojaClientes  = $this->buscarHoja($datos, ['CLIENTES_CONTRATOS', 'CLIENTES CONTRATOS', 'CLIENTES_Y_CONTRATOS', 'CLIENTES', 'CONTRATOS', 'HOJA 1', 'HOJA1'], 0);
        $hojaPagos     = $this->buscarHoja($datos, ['HISTORIAL_PAGOS', 'HISTORIAL PAGOS', 'PAGOS', 'HISTORIAL_DE_PAGOS', 'HISTORIAL', 'HOJA 2', 'HOJA2']);

        DB::beginTransaction();

        try {
            // FASE 0: Lotes opcionales
            if (!empty($hojaLotes)) {
                [$errLotes, $advLotes, $resLotes] = $this->procesarLotes($hojaLotes, $lotificacion, $modo);
                $errores      = array_merge($errores, $errLotes);
                $advertencias = array_merge($advertencias, $advLotes);
                $resumen["lotes_creados"] += $resLotes;
            }
            if (!empty($errores)) { DB::rollBack(); return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, false, $lotificacion->id); }

            // FASE 1: Clientes y Contratos (Obligatoria)
            if (empty($hojaClientes)) {
                DB::rollBack();
                $hojasEncontradas = implode(', ', array_keys($datos));
                return back()->with("error", "La hoja CLIENTES_CONTRATOS no fue encontrada o está vacía. Hojas encontradas en el archivo: [{$hojasEncontradas}]");
            }
            [$errClientes, $advClientes, $resClientes, $mapeoVentas] = $this->procesarClientesContratos($hojaClientes, $lotificacion, $modo);
            $errores      = array_merge($errores, $errClientes);
            $advertencias = array_merge($advertencias, $advClientes);
            $resumen["clientes_nuevos"]     += $resClientes["clientes_nuevos"];
            $resumen["clientes_existentes"] += $resClientes["clientes_existentes"];
            $resumen["contratos"]           += $resClientes["contratos"];
            if (!empty($errores)) { DB::rollBack(); return $this->respuestaResultado($errores, $advertencias, $resumen, $modo, false, $lotificacion->id); }

            // FASE 2: Historial de pagos (Opcional)
            if (!empty($hojaPagos)) {
                [$errPagos, $advPagos, $numPagos] = $this->procesarPagos($hojaPagos, $mapeoVentas, $lotificacion, $modo);
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

    /**
     * Busca una hoja en el array de datos con nombres flexibles o fallback de índice.
     */
    private function buscarHoja(array $datos, array $posiblesNombres, ?int $indiceFallback = null): array
    {
        // 1. Coincidencia exacta
        foreach ($posiblesNombres as $nombre) {
            if (isset($datos[$nombre]) && !empty($datos[$nombre])) {
                return $datos[$nombre];
            }
        }

        // 2. Coincidencia normalizada (sin espacios, sin guiones, mayúsculas)
        $normalizar = fn($str) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $str));
        $normPosibles = array_map($normalizar, $posiblesNombres);

        foreach ($datos as $sheetName => $filas) {
            $normSheet = $normalizar($sheetName);
            if (in_array($normSheet, $normPosibles)) {
                return $filas;
            }
            foreach ($normPosibles as $np) {
                if ($np !== '' && (str_contains($normSheet, $np) || str_contains($np, $normSheet))) {
                    return $filas;
                }
            }
        }

        // 3. Fallback por índice numérico
        if ($indiceFallback !== null) {
            $valores = array_values($datos);
            if (isset($valores[$indiceFallback]) && !empty($valores[$indiceFallback])) {
                return $valores[$indiceFallback];
            }
        }

        return [];
    }

    private function parsearExcel(string $ruta, ?string $originalName = null): array
    {
        // 1. Probar si es un archivo XML Spreadsheet 2003
        $primerosBytes = file_get_contents($ruta, false, null, 0, 1000);
        if ($primerosBytes && (str_contains($primerosBytes, 'urn:schemas-microsoft-com:office:spreadsheet') || str_contains($primerosBytes, '<?mso-application progid="Excel.Sheet"?>'))) {
            return $this->parsearXmlSpreadsheet($ruta);
        }

        // 2. Abrir como archivo ZIP OpenXML (.xlsx)
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            // Intentar como XML por si acaso
            try {
                return $this->parsearXmlSpreadsheet($ruta);
            } catch (\Throwable $t) {
                throw new \Exception("No se pudo abrir el archivo Excel. Asegúrese de que sea un archivo .xlsx válido y no esté protegido con contraseña.");
            }
        }

        // Shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName("xl/sharedStrings.xml");
        if ($ssXml) {
            if (preg_match_all('/<si>(.*?)<\/si>/s', $ssXml, $siMatches)) {
                foreach ($siMatches[1] as $si) {
                    if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si, $tMatches)) {
                        $sharedStrings[] = html_entity_decode(implode('', $tMatches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // Relaciones entre hojas y archivos XML
        $sheetRels = [];
        $relsXml = $zip->getFromName("xl/_rels/workbook.xml.rels");
        if ($relsXml) {
            if (preg_match_all('/<Relationship[^>]+Id="([^"]+)"[^>]+Target="([^"]+)"/i', $relsXml, $relMatches, PREG_SET_ORDER)) {
                foreach ($relMatches as $rm) {
                    $sheetRels[$rm[1]] = $rm[2];
                }
            }
        }

        // Hojas definidas en workbook.xml
        $wbXml = $zip->getFromName("xl/workbook.xml");
        $sheetDefs = [];
        if ($wbXml) {
            if (preg_match_all('/<sheet[^>]+name="([^"]+)"[^>]+(?:r:id|id)="([^"]+)"/i', $wbXml, $sheetMatches, PREG_SET_ORDER)) {
                foreach ($sheetMatches as $sm) {
                    $sheetDefs[$sm[1]] = $sm[2]; // name => rId
                }
            }
        }

        $stylesXml    = $zip->getFromName("xl/styles.xml");
        $dateStyleIds = $this->obtenerEstilosFecha($stylesXml ?: null);

        $resultado = [];

        // Leer hojas mapeadas por nombre
        if (!empty($sheetDefs)) {
            foreach ($sheetDefs as $nombre => $rId) {
                $target = $sheetRels[$rId] ?? null;
                $sheetPath = $target ? 'xl/' . ltrim(str_replace('xl/', '', $target), '/') : null;
                $sheetXml = ($sheetPath && $zip->locateName($sheetPath) !== false) ? $zip->getFromName($sheetPath) : null;

                if (!$sheetXml && $target) {
                    $sheetXml = $zip->getFromName($target);
                }

                if ($sheetXml) {
                    $filas = $this->parsearHoja($sheetXml, $sharedStrings, $dateStyleIds);
                    $resultado[trim($nombre)] = $filas;
                }
            }
        }

        // Fallback: si por alguna razón no se leyeron hojas, buscar archivos sheet1.xml, sheet2.xml, etc.
        if (empty($resultado)) {
            for ($i = 1; $i <= 10; $i++) {
                $sheetPath = "xl/worksheets/sheet{$i}.xml";
                if ($zip->locateName($sheetPath) !== false) {
                    $sheetXml = $zip->getFromName($sheetPath);
                    $filas = $this->parsearHoja($sheetXml, $sharedStrings, $dateStyleIds);
                    $sheetName = match($i) {
                        1 => 'CLIENTES_CONTRATOS',
                        2 => 'HISTORIAL_PAGOS',
                        3 => 'CATALOGO_LOTES',
                        default => "HOJA_{$i}"
                    };
                    $resultado[$sheetName] = $filas;
                }
            }
        }

        $zip->close();
        return $resultado;
    }

    /**
     * Parser para plantillas en formato XML Spreadsheet 2003.
     */
    private function parsearXmlSpreadsheet(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        $xml = simplexml_load_string($contenido);
        if (!$xml) {
            throw new \Exception("Formato XML inválido.");
        }

        $resultado = [];
        foreach ($xml->Worksheet as $ws) {
            $sheetName = trim((string)$ws->attributes('ss', true)['Name'] ?? (string)$ws['Name'] ?? 'Hoja');
            $filas = [];
            $header = [];

            if (isset($ws->Table->Row)) {
                foreach ($ws->Table->Row as $row) {
                    $rowData = [];
                    $colIdx = 0;
                    foreach ($row->Cell as $cell) {
                        $colIdx++;
                        $val = trim((string)($cell->Data ?? ''));
                        $rowData['C' . $colIdx] = $val;
                    }

                    if (empty(array_filter($rowData, fn($v) => $v !== ''))) {
                        continue;
                    }

                    if (empty($header)) {
                        $firstVal = strtolower(reset($rowData) ?: '');
                        if (count($rowData) <= 2 || str_starts_with($firstVal, 'hoja') || str_starts_with($firstVal, 'guia')) {
                            continue;
                        }
                        foreach ($rowData as $col => $enc) {
                            $header[$col] = strtolower(str_replace([' ', '-'], '_', trim($enc)));
                        }
                    } else {
                        $filaMapeada = [];
                        foreach ($header as $col => $campo) {
                            $filaMapeada[$campo] = $rowData[$col] ?? '';
                        }
                        $filas[] = $filaMapeada;
                    }
                }
            }
            $resultado[$sheetName] = $filas;
        }

        return $resultado;
    }

    private function parsearHoja(string $xml, array $sharedStrings, array $dateStyleIds): array
    {
        $sheet = simplexml_load_string($xml);
        $filas = []; $header = [];
        if (!$sheet || !isset($sheet->sheetData->row)) {
            return [];
        }

        $palabrasClaveEncabezado = [
            'expediente_num', 'expediente', 'nombres_apellidos', 'nombre', 'identificacion',
            'identificacion_cliente', 'nombre_bloque', 'bloque', 'numero_lote', 'lote',
            'fecha_venta', 'precio_final', 'plazo_meses', 'cuota_mensual', 'estado_contrato',
            'fecha_pago', 'monto_abonado', 'tipo_pago', 'area_metros', 'precio_base'
        ];

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $col      = preg_replace("/[0-9]/", "", (string)$cell["r"]);
                $type     = (string)$cell["t"];
                $styleId  = (int)($cell["s"] ?? -1);
                $raw      = (string)($cell->v ?? "");

                if ($type === "s") {
                    $valor = $sharedStrings[(int)$raw] ?? "";
                } elseif ($type === "inlineStr") {
                    $valor = (string)($cell->is->t ?? "");
                    if ($valor === "" && isset($cell->is->r)) {
                        foreach ($cell->is->r as $r) { $valor .= (string)($r->t ?? ""); }
                    }
                } elseif ($type === "b") {
                    $valor = $raw === "1" ? "true" : "false";
                } elseif (in_array($styleId, $dateStyleIds) && is_numeric($raw) && $raw !== "") {
                    $valor = $this->excelSerialToDate($raw);
                } else {
                    $valor = $raw;
                }
                $rowData[$col] = trim($valor);
            }

            // Ignorar filas completamente vacías
            if (empty(array_filter($rowData, fn($v) => $v !== ""))) {
                continue;
            }

            // Detección inteligente de fila de encabezados:
            if (empty($header)) {
                $filaTexto = array_map(fn($v) => strtolower(str_replace([' ', '-'], '_', trim($v))), $rowData);
                $coincidencias = count(array_intersect($filaTexto, $palabrasClaveEncabezado));

                // Si tiene al menos 2 columnas que coinciden con los nombres de campos estándar:
                if ($coincidencias >= 2) {
                    foreach ($rowData as $col => $enc) {
                        $header[$col] = strtolower(str_replace([" ", "-"], "_", trim($enc)));
                    }
                    continue;
                }

                // O si es la primera fila con más de 3 celdas no vacías y no es banner:
                $primeraCelda = strtolower(reset($rowData) ?: '');
                if (!str_starts_with($primeraCelda, 'hoja') && !str_starts_with($primeraCelda, 'guia') && count($rowData) >= 4) {
                    foreach ($rowData as $col => $enc) {
                        $header[$col] = strtolower(str_replace([" ", "-"], "_", trim($enc)));
                    }
                    continue;
                }

                continue; // Saltar fila de título / banner
            } else {
                $filaMapeada = [];
                foreach ($header as $col => $campo) {
                    $filaMapeada[$campo] = $rowData[$col] ?? "";
                }
                $filas[] = $filaMapeada;
            }
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
