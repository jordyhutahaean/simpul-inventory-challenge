<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryRecord extends Model
{
    //
    protected $fillable = ['reference','warehouse_id','day_of_week','hour','duration_minutes','notes'];
    protected $casts = ['hour' => 'float'];
}