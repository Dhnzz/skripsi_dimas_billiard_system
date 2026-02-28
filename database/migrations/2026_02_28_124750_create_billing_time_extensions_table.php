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
        Schema::create('billing_time_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_id')->constrained();
            $table->decimal('added_hours', 4, 2);
            $table->decimal('price_per_hour', 4, 2);
            $table->decimal('total_price', 4, 2);
            $table->foreignId('extended_by')->constrained('users');
            $table->timestamp('new_scheduled_end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_time_extensions');
    }
};
