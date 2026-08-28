@extends('template')

@section('titulo', 'Lista de Reservas')

@section('contenido')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-calendar-alt text-primary"></i> Reservas de Lotes</h1>
    <a href="{{ route('reservas.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Nueva Reserva
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Historial de Reservas</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Lotes</th>
                        <th>Monto Reserva</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservas as $reserva)
                    <tr>
                        <td>{{ $reserva->id_reserva }}</td>
                        <td>{{ $reserva->cliente->nombres_apellidos }}</td>
                        <td>
                            @foreach($reserva->lotes as $lote)
                                <span class="badge bg-info text-dark">L-{{ $lote->numero_lote }}</span>
                            @endforeach
                        </td>
                        <td>${{ number_format($reserva->monto_reserva, 2) }}</td>
                        <td>
                            {{ $reserva->fecha_vencimiento->format('d/m/Y') }}
                            @if($reserva->estado == 'Activa' && $reserva->fecha_vencimiento < now())
                                <span class="badge bg-danger">Vencida</span>
                            @endif
                        </td>
                        <td>
                            @if($reserva->estado == 'Activa')
                                <span class="badge bg-primary">Activa</span>
                            @elseif($reserva->estado == 'Formalizada')
                                <span class="badge bg-success">Formalizada</span>
                            @else
                                <span class="badge bg-secondary">{{ $reserva->estado }}</span>
                            @endif
                        </td>
                        <td>
                            @if($reserva->estado == 'Activa')
                                <a href="{{ route('reservas.formalizar', $reserva->id_reserva) }}" class="btn btn-sm btn-success" title="Formalizar Venta"><i class="fas fa-check"></i> Formalizar</a>
                                <form action="{{ route('reservas.anular', $reserva->id_reserva) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de anular esta reserva y liberar los lotes?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" title="Anular Reserva"><i class="fas fa-times"></i> Anular</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
