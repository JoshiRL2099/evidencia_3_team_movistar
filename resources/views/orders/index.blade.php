@extends('layouts.app')

@section('content')

<div class="container">
    <h2>Lista de Órdenes</h2>

    @php $__role = auth()->user()->role->name ?? ''; @endphp

    @if(in_array($__role, ['ADMIN','SALES']))
        <a href="{{ route('orders.create') }}" class="btn btn-primary mb-3">Nueva Orden</a>
    @endif

    @if($__role === 'ADMIN')
        <a href="{{ route('orders.trash') }}" class="btn btn-secondary mb-3">Papelera</a>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Factura</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->invoice_number }}</td>
                    <td>{{ $order->customer->display_name ?? 'N/A' }}</td>
                    <td>{{ $order->createdBy->full_name ?? 'N/A' }}</td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>{{ $order->order_datetime }}</td>
                    <td>{{ $order->status }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order->order_id) }}" class="btn btn-info btn-sm">Ver</a>

                        @if(in_array($__role, ['ADMIN','SALES']))
                            <a href="{{ route('orders.edit', $order->order_id) }}" class="btn btn-warning btn-sm">Editar</a>
                        @endif

                        @if($__role === 'ADMIN')
                            <form action="{{ route('orders.destroy', $order->order_id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay órdenes registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection