<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Recibo N° {{ $pago->id_abono }}
    </title>

    <link rel="stylesheet" href="{{ asset('css/recibo.css') }}">
</head>

<body onload="window.print()">

    <div class="pagina-recibo">

        <div class="recibo-container">

            {{-- =====================================================
                 RECIBO IZQUIERDO
                 ===================================================== --}}

            {{-- Número de recibo --}}
            <span class="recibo-data recibo-izq-numero">
                {{ $pago->id_abono }}
            </span>

            {{-- Número PV / referencia --}}
            <span class="recibo-data recibo-izq-pv">
                {{ $cliente->pv_num ?? '' }}
            </span>

            {{-- Nombre del cliente --}}
            <span class="recibo-data recibo-izq-nombre">
                {{ $cliente->nombres_apellidos ?? 'Cliente Desconocido' }}
            </span>

            {{-- Monto en dólares --}}
            <span class="recibo-data recibo-izq-monto">
                {{ number_format($pago->monto_abonado, 2) }} 
            </span>

            {{-- Monto en letras --}}
            <span class="recibo-data recibo-izq-letras">
                {{ $monto_en_letras }} NETOS
            </span>

            {{-- Concepto --}}
            <span class="recibo-data recibo-izq-concepto">
                {{ $pago->tipo_pago }}
            </span>

            {{-- Detalle del lote y montos (línea 1) --}}
            <span class="recibo-data recibo-izq-detalle1">
                Lotes: {{ $lotes_texto }}
            </span>

            {{-- Detalle del lote y montos (línea 2) --}}
            <span class="recibo-data recibo-izq-detalle2">
                Total: ${{ number_format($valor_total, 2) }} | Abonado: ${{ number_format($total_abonado, 2) }} | Pendiente: ${{ number_format($saldo_pendiente, 2) }}
            </span>

            {{-- Detalle del lote y montos (línea 3) --}}
            <span class="recibo-data recibo-izq-detalle3">
                Cuota: ${{ number_format($venta->cuota_mensual, 2) }}/mes | Plazo: {{ $venta->plazo_meses }} meses | Faltan: {{ $abonos_faltantes }}
            </span>


            {{-- Día --}}
            <span class="recibo-data recibo-izq-dia">
                {{ date('d', strtotime($pago->fecha_pago)) }}
            </span>

            {{-- Mes --}}
            <span class="recibo-data recibo-izq-mes">
                {{ ucfirst(\Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->monthName) }}
            </span>

            {{-- Año --}}
            <span class="recibo-data recibo-izq-anio">
                {{ date('Y', strtotime($pago->fecha_pago)) }}
            </span>


            {{-- =====================================================
                 RECIBO DERECHO
                 ===================================================== --}}

            {{-- Número de recibo --}}
            <span class="recibo-data recibo-der-numero">
                {{ $pago->id_abono }}
            </span>

            {{-- Número PV / referencia --}}
            <span class="recibo-data recibo-der-pv">
                {{ $cliente->pv_num ?? '' }}
            </span>

            {{-- Nombre del cliente --}}
            <span class="recibo-data recibo-der-nombre">
                {{ $cliente->nombres_apellidos ?? 'Cliente Desconocido' }}
            </span>

            {{-- Monto en dólares --}}
            <span class="recibo-data recibo-der-monto">
                {{ number_format($pago->monto_abonado, 2) }}
            </span>

            {{-- Monto en letras --}}
            <span class="recibo-data recibo-der-letras">
                {{ $monto_en_letras }} NETOS
            </span>

            {{-- Concepto --}}
            <span class="recibo-data recibo-der-concepto">
                {{ $pago->tipo_pago }}
            </span>

            {{-- Detalle del lote y montos (línea 1) --}}
            <span class="recibo-data recibo-der-detalle1">
                Lotes: {{ $lotes_texto }}
            </span>

            {{-- Detalle del lote y montos (línea 2) --}}
            <span class="recibo-data recibo-der-detalle2">
                Total: ${{ number_format($valor_total, 2) }} | Abonado: ${{ number_format($total_abonado, 2) }} | Pendiente: ${{ number_format($saldo_pendiente, 2) }}
            </span>

            {{-- Detalle del lote y montos (línea 3) --}}
            <span class="recibo-data recibo-der-detalle3">
                Cuota: ${{ number_format($venta->cuota_mensual, 2) }}/mes | Plazo: {{ $venta->plazo_meses }} cuotas | Faltan: {{ $abonos_faltantes }}
            </span>

            {{-- Día --}}
            <span class="recibo-data recibo-der-dia">
                {{ date('d', strtotime($pago->fecha_pago)) }}
            </span>

            {{-- Mes --}}
            <span class="recibo-data recibo-der-mes">
                {{ ucfirst(\Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->monthName) }}
            </span>

            {{-- Año --}}
            <span class="recibo-data recibo-der-anio">
                {{ date('Y', strtotime($pago->fecha_pago)) }}
            </span>

        </div>

    </div>

</body>
</html>