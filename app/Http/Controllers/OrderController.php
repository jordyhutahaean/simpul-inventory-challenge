<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\WarehouseAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;

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
    
    public function confirm(Order $order)
    {
        if ($order->confirmed_at) {
            return redirect()->route('orders.confirmation', $order);
        }

        if ($order->status === 'unfulfillable') {
            return back()->withErrors('This order cannot be confirmed: no stock available.');
        }

        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items()->whereNotNull('warehouse_id')->get() as $item) {
                    $inv = Inventory::where('warehouse_id', $item->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inv || $inv->quantity < $item->quantity) {
                        throw new \RuntimeException('Stock changed since this proposal was made.');
                    }

                    $inv->decrement('quantity', $item->quantity);
                }

                $order->update(['confirmed_at' => now()]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage() . ' Please submit the order again.');
        }

        return redirect()->route('orders.confirmation', $order);
    }

    public function confirmation(Order $order)
    {
        if (!$order->confirmed_at) {
            return redirect()->route('orders.show', $order);
        }

        $order->load('items.product', 'items.warehouse');

        return view('orders.confirmation', ['order' => $order]);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'items.warehouse');

        return view('orders.show', ['order' => $order]);
    }
}