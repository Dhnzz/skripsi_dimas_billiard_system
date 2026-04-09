<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // Status perangkat lampu/device di meja.
            // true  → billing aktif, lampu harus MENYALA
            // false → tidak ada billing aktif, lampu harus MATI
            $table->boolean('device_status')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn('device_status');
        });
    }
};
