<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    //
    protected $fillable =[
        'code', 'name', 'location', 'distance_km',
        'has_refrigerated_storage', 'has_refrigerated_vans'
    ];

    protected $casts = [
        'distance_km' => 'float',
        'has_refrigerated_storage' => 'boolean',
        'has_refrigerated_vans' => 'boolean',
    ];

    public function inventories(){
        return $this->hasMany(Inventory::class);
    }
}
