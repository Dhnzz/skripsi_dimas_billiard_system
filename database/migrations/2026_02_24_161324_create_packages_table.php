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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type',['normal','loss']);
            $table->decimal('duration_hours', 4, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->foreignId('pricing_id')->nullable()->constrained();
            $table->text('description')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
