<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableLightController extends Controller
{
    /**
     * Mengembalikan status lampu meja untuk microcontroller.
     *
     * Endpoint ini dikonsumsi oleh microcontroller di setiap meja billiard.
     * Microcontroller akan melakukan polling ke endpoint ini secara berkala
     * untuk menentukan apakah lampu meja harus menyala atau mati.
     *
     * Response:
     *   - light_on: true  → lampu MENYALA (billing sedang aktif)
     *   - light_on: false → lampu MATI    (tidak ada billing aktif)
     *
     * @param  int  $tableId  ID meja yang dikueri microcontroller
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(int $tableId): JsonResponse
    {
        $table = Table::find($tableId);

        // Jika meja tidak ditemukan atau meja tidak aktif
        if (!$table || !$table->is_active) {
            return response()->json([
                'table_id'  => $tableId,
                'light_on'  => false,
                'message'   => 'Meja tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        // Cek apakah ada billing yang sedang aktif di meja ini
        $hasActiveBilling = $table->activeBilling()->exists();

        return response()->json([
            'table_id'  => $table->id,
            'light_on'  => $hasActiveBilling,
            'message'   => $hasActiveBilling
                ? 'Billing sedang berjalan. Lampu menyala.'
                : 'Tidak ada billing aktif. Lampu mati.',
        ]);
    }

    /**
     * Mengembalikan status lampu untuk SEMUA meja sekaligus.
     *
     * Berguna jika ada satu kontroler pusat yang mengelola semua lampu meja.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statusAll(): JsonResponse
    {
        $tables = Table::where('is_active', true)
            ->with(['activeBilling'])
            ->get();

        $data = $tables->map(function ($table) {
            $hasActiveBilling = $table->activeBilling !== null;

            return [
                'table_id'  => $table->id,
                'table_name'=> $table->name,
                'light_on'  => $hasActiveBilling,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
