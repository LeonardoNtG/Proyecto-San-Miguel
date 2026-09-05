{!! '<?xml version="1.0" encoding="UTF-8"?>' . "\n" !!}
{!! '<?mso-application progid="Excel.Sheet"?>' . "\n" !!}
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
  <Styles>
    <Style ss:ID="Header">
      <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>
      <Interior ss:Color="#1A3A6B" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
      <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>
    </Style>
    <Style ss:ID="Requerido">
      <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>
      <Interior ss:Color="#C0392B" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="Opcional">
      <Font ss:Bold="1" ss:Color="#000000" ss:Size="10"/>
      <Interior ss:Color="#F0E68C" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="Dato">
      <Font ss:Size="10"/>
      <Alignment ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="Titulo">
      <Font ss:Bold="1" ss:Size="14" ss:Color="#1A3A6B"/>
      <Alignment ss:Horizontal="Left"/>
    </Style>
  </Styles>

  <!-- ========== HOJA 1: CLIENTES_CONTRATOS ========== -->
  <Worksheet ss:Name="CLIENTES_CONTRATOS">
    <Table ss:DefaultRowHeight="18">
      <!-- Fila de titulo -->
      <Row ss:Height="30">
        <Cell ss:StyleID="Titulo" ss:MergeAcross="18"><Data ss:Type="String">HOJA 1 - CLIENTES Y CONTRATOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales</Data></Cell>
      </Row>
      <!-- Encabezados -->
      <Row ss:Height="25">
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">expediente_num</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombres_apellidos</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">identificacion</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">telefono</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">direccion</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">estado_civil</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">oficio</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">pv_num</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">fecha_venta</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">precio_final</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">plazo_meses</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">cuota_mensual</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">estado_contrato</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">prima_pagada</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">fecha_prima</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">beneficiario_final</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">nota_beneficiario</Data></Cell>
      </Row>
      <!-- Ejemplo fila 1 -->
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">EXP-0001</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Maria Karina Perez Lopez</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">89095854</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">De Claro 2c al sur este</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Soltera</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Maestra</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">PV-2024-001</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">30/08/2026</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9000.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">60</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Vigente</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">500.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">30/08/2026</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
      </Row>
      <!-- Ejemplo fila 2 (mismo cliente, segundo lote) -->
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">EXP-0001</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Maria Karina Perez Lopez</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">89095854</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">De Claro 2c al sur este</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Soltera</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Maestra</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">PV-2024-002</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-02</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">30/08/2026</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9000.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">60</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Vigente</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">0</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
      </Row>
    </Table>
  </Worksheet>

  <!-- ========== HOJA 2: HISTORIAL_PAGOS ========== -->
  <Worksheet ss:Name="HISTORIAL_PAGOS">
    <Table ss:DefaultRowHeight="18">
      <Row ss:Height="30">
        <Cell ss:StyleID="Titulo" ss:MergeAcross="9"><Data ss:Type="String">HOJA 2 - HISTORIAL DE PAGOS | Campos en ROJO son obligatorios | Campos en AMARILLO son opcionales</Data></Cell>
      </Row>
      <Row ss:Height="25">
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">identificacion_cliente</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">fecha_pago</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">monto_abonado</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">tipo_pago</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">metodo_pago</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">referencia</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">cuenta_destino</Data></Cell>
        <Cell ss:StyleID="Opcional"><Data ss:Type="String">numero_recibo_original</Data></Cell>
      </Row>
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">15/09/2026</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Cuota</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Efectivo</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">REC-001</Data></Cell>
      </Row>
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">001-230489-0001X</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">15/10/2026</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">150.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Cuota</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Transferencia Bancaria</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">REF-20241015</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">BANPRO 1234-5678</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">REC-002</Data></Cell>
      </Row>
    </Table>
  </Worksheet>

  <!-- ========== HOJA 3: CATALOGO_LOTES ========== -->
  <Worksheet ss:Name="CATALOGO_LOTES">
    <Table ss:DefaultRowHeight="18">
      <Row ss:Height="30">
        <Cell ss:StyleID="Titulo" ss:MergeAcross="4"><Data ss:Type="String">HOJA 3 - CATALOGO DE LOTES (Opcional) | Solo si hay lotes que no existen aun en el sistema</Data></Cell>
      </Row>
      <Row ss:Height="25">
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">nombre_bloque</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">numero_lote</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">area_metros</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">precio_base</Data></Cell>
        <Cell ss:StyleID="Requerido"><Data ss:Type="String">estado</Data></Cell>
      </Row>
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-01</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">176.25</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9000.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Vendido</Data></Cell>
      </Row>
      <Row ss:Height="18">
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Bloque A</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">A-02</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">180.50</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="Number">9500.00</Data></Cell>
        <Cell ss:StyleID="Dato"><Data ss:Type="String">Disponible</Data></Cell>
      </Row>
    </Table>
  </Worksheet>

  <!-- ========== HOJA 4: INSTRUCCIONES ========== -->
  <Worksheet ss:Name="INSTRUCCIONES">
    <Table ss:DefaultRowHeight="16">
      <Row ss:Height="30"><Cell ss:StyleID="Titulo" ss:MergeAcross="1"><Data ss:Type="String">GUIA DE IMPORTACION MASIVA DE CLIENTES</Data></Cell></Row>
      <Row ss:Height="12"><Cell><Data ss:Type="String"></Data></Cell></Row>
      <Row><Cell ss:StyleID="Header"><Data ss:Type="String">HOJA</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">DESCRIPCION</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">CLIENTES_CONTRATOS</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Una fila por contrato. Si un cliente tiene 2 lotes, son 2 filas con la misma cedula.</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">HISTORIAL_PAGOS</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Un registro por cada abono o pago realizado historicamente.</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">CATALOGO_LOTES</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Opcional. Solo si los lotes aun no existen en el sistema.</Data></Cell></Row>
      <Row ss:Height="12"><Cell><Data ss:Type="String"></Data></Cell></Row>
      <Row><Cell ss:StyleID="Header"><Data ss:Type="String">CAMPO</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">VALORES PERMITIDOS</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">estado_contrato</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Vigente | Rescindido | Finalizado</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">tipo_pago</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Prima | Cuota | Abono Extraordinario | Cancelacion</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">metodo_pago</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Efectivo | Transferencia Bancaria | Deposito Bancario | Cheque</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">estado (lotes)</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Disponible | Reservado | Vendido</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">fechas</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Formato DD/MM/AAAA Ejemplo: 30/08/2026</Data></Cell></Row>
      <Row><Cell ss:StyleID="Dato"><Data ss:Type="String">telefonos</Data></Cell><Cell ss:StyleID="Dato"><Data ss:Type="String">Guardar como TEXTO, no como numero, para preservar ceros iniciales</Data></Cell></Row>
    </Table>
  </Worksheet>

</Workbook>
