<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    //
    protected $fillable = ['warehouse_id', 'product_id', 'quantity'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function confirm(Order $order)
        {
            if ($order->confirmed_at) {
                return redirect()->route('orders.show', $order);
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

            return redirect()->route('orders.show', $order);
        }
}
