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
        Schema::create('delivery_records', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();            // DL001
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');       // 0 = Mon ... 6 = Sun
            $table->decimal('hour', 4, 2);                    // 7.50 = 07:30
            $table->unsignedInteger('duration_minutes');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_records');
    }
};
