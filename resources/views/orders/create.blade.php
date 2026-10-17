@extends('layouts.app')

@section('title', 'New Order')

@section('content')
<div class="card">
    <h1>New Order</h1>
    <p class="muted">Add one or more products. The system will propose which warehouse(s) should fulfill the order.</p>

    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div style="margin-bottom: 16px;">
            <div style="margin-bottom: 16px;">
                <label for="ordered_at">Order time (optional, defaults to now)</label>
                <input type="datetime-local" id="ordered_at" name="ordered_at" value="{{ old('ordered_at') }}">
                <p class="muted">Pick a time to see how rush hour or weekend traffic would affect the choice.</p>
            </div>
            <label for="customer_name">Customer name (optional)</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}">
        </div>

        <div id="lines">
            @php($oldLines = old('lines', [['product_id' => '', 'quantity' => 1]]))
            @foreach ($oldLines as $i => $line)
                <div class="row line">
                    <div class="product">
                        <label>Product</label>
                        <select name="lines[{{ $i }}][product_id]" required>
                            <option value="">Select a product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>
                                    {{ $product->sku }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="qty">
                        <label>Quantity</label>
                        <input type="number" name="lines[{{ $i }}][quantity]" min="1" value="{{ $line['quantity'] ?? 1 }}" required>
                    </div>
                    <div style="padding-bottom: 8px;">
                        <label style="display:flex; gap:6px; align-items:center; white-space:nowrap;">
                            <input type="checkbox" name="lines[{{ $i }}][urgent]" value="1" @checked(!empty($line['urgent']))>
                            Urgent
                        </label>
                    </div>
                    <button type="button" class="secondary remove">Remove</button>
                </div>
            @endforeach
        </div>

        <div style="display:flex; gap:10px; margin-top: 8px;">
            <button type="button" class="secondary" id="add-line">+ Add product</button>
            <button type="submit">Find warehouse</button>
        </div>
    </form>
</div>

{{-- Template for new rows; __INDEX__ is replaced by JS --}}
<template id="line-template">
    <div class="row line">
        <div class="product">
            <label>Product</label>
            <select name="lines[__INDEX__][product_id]" required>
                <option value="">Select a product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="qty">
            <label>Quantity</label>
            <input type="number" name="lines[__INDEX__][quantity]" min="1" value="1" required>
        </div>
        <div style="padding-bottom: 8px;">
                        <label style="display:flex; gap:6px; align-items:center; white-space:nowrap;">
                            <input type="checkbox" name="lines[{{ $i }}][urgent]" value="1" @checked(!empty($line['urgent']))>
                            Urgent
                        </label>
                    </div>
        <button type="button" class="secondary remove">Remove</button>
    </div>
</template>

<script>
    const linesEl = document.getElementById('lines');
    const template = document.getElementById('line-template').innerHTML;
    let nextIndex = linesEl.querySelectorAll('.line').length;

    document.getElementById('add-line').addEventListener('click', () => {
        linesEl.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
    });

    linesEl.addEventListener('click', (e) => {
        if (!e.target.classList.contains('remove')) return;
        if (linesEl.querySelectorAll('.line').length > 1) {
            e.target.closest('.line').remove();
        }
    });
</script>
@endsection