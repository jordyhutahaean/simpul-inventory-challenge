@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
@php
    $allocated  = $order->items->whereNotNull('warehouse_id')->groupBy('warehouse_id');
    $shortfalls = $order->items->whereNull('warehouse_id');
    $statusLabel = [
        'full' => 'Fully covered',
        'partial' => 'Partially covered',
        'unfulfillable' => 'Cannot be fulfilled',
    ][$order->status] ?? $order->status;
@endphp

<div class="card">
    <h1>
        Order #{{ $order->id }}
        <span class="badge {{ $order->status }}">{{ $statusLabel }}</span>
        @if ($order->confirmed_at)
            <span class="badge confirmed">Confirmed</span>
        @endif
    </h1>
    @if ($order->customer_name)
        <p class="muted">Customer: {{ $order->customer_name }}</p>
    @endif
    <p class="muted">
        @if ($allocated->count() > 0)
            <p class="muted">{{ $allocated->count() === 1 ? '1 shipment' : $allocated->count() . ' shipments' }}</p>
        @endif
    </p>
</div>

@foreach ($allocated as $items)
    @php($warehouse = $items->first()->warehouse)
    <div class="card">
        <h2>
            {{ $warehouse->name }}
            <span class="muted">({{ number_format($warehouse->distance_km, 1) }} km away)</span>
        </h2>
        <p class="muted">
            {{ $warehouse->has_refrigerated_storage ? 'Refrigerated storage' : 'No refrigerated storage' }}
            &middot;
            {{ $warehouse->has_refrigerated_vans ? 'Refrigerated vans' : 'Standard vehicles' }}
        </p>

        <table>
            <thead>
                <tr><th>SKU</th><th>Product</th><th>Quantity</th></tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->product->sku }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @foreach ($items as $item)
            @if ($item->product->requires_cold_chain && !$warehouse->has_refrigerated_vans)
                <div class="warning">
                    Cold-chain notice: {{ $item->product->name }} ships from this warehouse with
                    refrigerated storage but standard vehicles.
                </div>
            @endif
        @endforeach
    </div>
@endforeach

@if ($shortfalls->isNotEmpty())
    <div class="card">
        <h2>Shortfall</h2>
        <p class="muted">These quantities could not be supplied from any eligible warehouse.</p>
        <table>
            <thead>
                <tr><th>SKU</th><th>Product</th><th>Missing</th></tr>
            </thead>
            <tbody>
                @foreach ($shortfalls as $item)
                    <tr>
                        <td>{{ $item->product->sku }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="card">
    <h2>Why this decision?</h2>
    <ul class="explain">
        @foreach ($order->explanation as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ul>
</div>

<div class="card" style="display:flex; gap:10px; align-items:center;">
    @if ($order->confirmed_at)
        <span class="muted">Confirmed on {{ $order->confirmed_at->format('d M Y, H:i') }}. Stock has been reserved.</span>
    @elseif ($order->status === 'unfulfillable')
        <span class="muted">This order cannot be confirmed because no stock is available.</span>
    @else
        <form method="POST" action="{{ route('orders.confirm', $order) }}">
            @csrf
            <button type="submit" class="confirm">
                {{ $order->status === 'partial' ? 'Confirm partial order' : 'Confirm order' }}
            </button>
        </form>
    @endif
    <a href="{{ route('orders.create') }}" class="btn secondary">New order</a>
</div>
@endsection