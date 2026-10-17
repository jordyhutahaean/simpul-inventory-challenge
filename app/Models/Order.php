<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['customer_name', 'status', 'strategy', 'explanation', 'confirmed_at', 'ordered_at'];

    protected $casts = ['explanation'=>'array','confirmed_at'=>'datetime', 'ordered_at' => 'datetime',];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}