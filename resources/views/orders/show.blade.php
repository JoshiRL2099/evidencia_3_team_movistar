@extends('layouts.app')

@section('content')

    @php $uRole = strtoupper(trim(auth()->user()->role->name ?? '')); @endphp

    <div class="container">
        <h2>Detalle de la Orden</h2>

        <div class="mb-3">
            <p><strong>Factura:</strong> {{ $order->invoice_number }}</p>
            <p><strong>Cliente:</strong> {{ $order->customer->display_name ?? 'N/A' }}</p>
            <p><strong>Usuario:</strong> {{ $order->createdBy->full_name ?? 'N/A' }}</p>
            <p><strong>Fecha:</strong> {{ $order->order_datetime }}</p>
            <p><strong>Estado:</strong> {{ $order->status }}</p>
            <p><strong>Notas:</strong> {{ $order->notes ?? 'Sin notas' }}</p>
        </div>

        <hr>

        <h4>Dirección de entrega</h4>
        @if($order->deliveryAddress)
            <p>
                {{ $order->deliveryAddress->street }}
                #{{ $order->deliveryAddress->ext_number }}
                @if($order->deliveryAddress->int_number)
                    Int. {{ $order->deliveryAddress->int_number }}
                @endif
            </p>
            <p>{{ $order->deliveryAddress->neighborhood }}, {{ $order->deliveryAddress->city }}, {{ $order->deliveryAddress->state }}</p>
            <p>CP {{ $order->deliveryAddress->zip }}</p>
            <p><strong>Referencias:</strong> {{ $order->deliveryAddress->references ?? 'N/A' }}</p>
        @else
            <p>No hay dirección registrada.</p>
        @endif

        <hr>

        <h4>Materiales</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Precio Unitario</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->product->unit ?? 'N/A' }}</td>
                        <td>${{ number_format($item->unit_price ?? 0, 2) }}</td>
                        <td>${{ number_format($item->quantity * ($item->unit_price ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay materiales registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>

        <hr>

        <h4>Evidencias (Fotos)</h4>

        <div id="gallery" class="mb-3">
            <div id="gallery-items" class="d-flex gap-2 flex-wrap mb-3">
                @forelse($order->photos as $photo)
                    <div class="text-center">
                        <a href="{{ $photo->url }}" target="_blank">
                            <img src="{{ $photo->url }}" alt="evidence" class="img-thumbnail" style="width:120px;height:120px;object-fit:cover;" />
                        </a>
                        <div class="small text-muted">{{ ($photo->type && $photo->type !== 'UNLOADED_EVIDENCE') ? $photo->type : 'Evidencia' }}</div>
                    </div>
                @empty
                    <div class="text-muted">No hay evidencias cargadas.</div>
                @endforelse
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Volver</a>
        </div>

    </div>

@endsection