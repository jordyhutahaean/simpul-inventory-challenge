<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\WarehouseAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create()
    {
        return view('orders.create', [
            'products' => Product::orderBy('sku')->get(),
        ]);
    }

    
    public function store(Request $request, WarehouseAllocator $allocator)
    {
        
        $data = $request->validate([
            'customer_name'        => 'nullable|string|max:255',
            'lines'                => 'required|array|min:1',
            'lines.*.product_id'   => 'required|exists:products,id',
            'lines.*.quantity'     => 'required|integer|min:1',
            'ordered_at'           => 'nullable|date',
            'lines.*.urgent'     => 'nullable|boolean', 
        ]);

        $when = isset($data['ordered_at']) ? \Carbon\Carbon::parse($data['ordered_at']) : now();
        $result = $allocator->allocate($data['lines'], $when);

        $order = DB::transaction(function () use ($result, $data, $when) {
            $order = Order::create([
                'customer_name' => $data['customer_name'] ?? null,
                'status'        => $result['status'],
                'strategy'      => $result['strategy'],
                'explanation'   => $result['explanation'],
                'ordered_at'    => $when,
            ]);

            foreach ($result['allocations'] as $alloc) {
                foreach ($alloc['items'] as $item) {
                    $order->items()->create([
                        'product_id'   => $item['product_id'],
                        'warehouse_id' => $alloc['warehouse']['id'],
                        'quantity'     => $item['quantity'],
                    ]);
                }
            }

            foreach ($result['shortfalls'] as $short) {
                $order->items()->create([
                    'product_id'   => Product::where('sku', $short['sku'])->value('id'),
                    'warehouse_id' => null,
                    'quantity'     => $short['missing'],
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'items.warehouse');

        return view('orders.show', ['order' => $order]);
    }
}