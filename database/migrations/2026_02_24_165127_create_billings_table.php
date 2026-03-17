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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->string('billing_code')->unique();
            $table->foreignId('booking_id')->nullable()->constrained();
            $table->foreignId('customer_id')->nullable()->constrained('users');
            $table->string('guest_name')->nullable();
            $table->foreignId('table_id')->constrained();
            $table->foreignId('package_id')->nullable()->constrained();
            $table->foreignId('pricing_id')->nullable()->constrained();
            $table->timestamp('started_at');
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('ended_at');
            $table->decimal('extra_duration_hours', 6, 2)->default(0);
            $table->decimal('actual_duration_hours', 6, 2)->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('extra_price', 10, 2)->default(0);
            $table->decimal('addon_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->enum('status', ['active','completed','cancelled'])->default('active');
            $table->foreignId('started_by')->constrained('users');
            $table->foreignId('ended_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
