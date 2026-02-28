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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('table_id')->constrained();
            $table->foreignId('package_id')->nullable()->constrained();
            $table->foreignId('pricing_id')->nullable()->constrained();
            $table->date('scheduled_date');
            $table->time('scheduled_start');
            $table->time('scheduled_end');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending','confirmed','rejected','cancelled','completed'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
