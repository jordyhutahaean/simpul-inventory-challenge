<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable = ['name', 'sku', 'category', 'requires_cold_chain'];
    protected $casts = ['requires_cold_chain' => 'boolean'];

    public function inventories() { return $this->hasMany(Inventory::class); }
}
