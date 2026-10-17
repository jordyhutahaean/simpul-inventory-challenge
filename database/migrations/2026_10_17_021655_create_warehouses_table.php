<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   //W01, W02, ...
            $table->string('name');
            $table->string('location');         //city
            $table->decimal('distance_km', 6 ,1);
            $table->boolean('has_refrigerated_storage')->default(false);
            $table->boolean('has_refrigerated_vans')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
