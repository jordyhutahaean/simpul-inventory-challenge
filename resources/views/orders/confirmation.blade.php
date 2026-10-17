@extends('layouts.app')

@section('title', 'Order #' . $order->id . ' confirmed')

@section('content')
@php
    $allocated  = $order->items->whereNotNull('warehouse_id')->groupBy('warehouse_id');
    $shortfalls = $order->items->whereNull('warehouse_id');
    $totalUnits = $order->items->whereNotNull('warehouse_id')->sum('quantity');
    $shipments  = $allocated->count();
@endphp

<div class="card" style="border-left: 6px solid #1e8e5a;">
    <h1>
        Order #{{ $order->id }} confirmed
        <span class="badge confirmed">Confirmed</span>
        @if ($order->status === 'partial')
            <span class="badge partial">Partially covered</span>
        @endif
    </h1>

    <p>
        {{ $totalUnits }} {{ $totalUnits === 1 ? 'unit' : 'units' }} reserved in
        {{ $shipments === 1 ? '1 shipment' : $shipments . ' shipments' }}.
        Stock has been deducted from the warehouses below.
    </p>

    <table>
        <tbody>
            @if ($order->customer_name)
                <tr><th>Customer</th><td>{{ $order->customer_name }}</td></tr>
            @endif
            @if ($order->ordered_at)
                <tr><th>Order time</th><td>{{ $order->ordered_at->format('D, d M Y, H:i') }}</td></tr>
            @endif
            <tr><th>Confirmed</th><td>{{ $order->confirmed_at->format('D, d M Y, H:i') }}</td></tr>
        </tbody>
    </table>
</div>

@foreach ($allocated as $items)
    @php($warehouse = $items->first()->warehouse)
    <div class="card">
        <h2>
            Shipment {{ $loop->iteration }}: {{ $warehouse->name }}
            <span class="muted">({{ number_format($warehouse->distance_km, 1) }} km away)</span>
        </h2>

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
    </div>
@endforeach

@if ($shortfalls->isNotEmpty())
    <div class="card">
        <h2>Not covered by this order</h2>
        <p class="muted">
            These quantities could not be supplied from any eligible warehouse and were not reserved.
            They will need to be ordered again once stock is available.
        </p>
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

<div class="card" style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('orders.create') }}" class="btn">Place another order</a>
    <a href="{{ route('orders.show', $order) }}" class="btn secondary">View decision details</a>
</div>
@endsection