<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class TableLightController extends Controller
{
    /**
     * Status lampu satu meja berdasarkan field device_status.
     *
     * Field `device_status` di tabel `tables`:
     *   - true  → billing sedang aktif, lampu MENYALA
     *   - false → tidak ada billing aktif, lampu MATI
     *
     * Microcontroller cukup polling endpoint ini dan membaca `light_on`.
     *
     * GET /api/microcontroller/table/{tableId}/light
     */
    public function status(int $tableId): JsonResponse
    {
        $table = Table::find($tableId);

        if (!$table || !$table->is_active) {
            return response()->json([
                'table_id'  => $tableId,
                'light_on'  => false,
                'message'   => 'Meja tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        return response()->json([
            'table_id'   => $table->id,
            'table_name' => $table->name,
            'light_on'   => (bool) $table->device_status,
            'message'    => $table->device_status
                ? 'Billing sedang berjalan. Lampu menyala.'
                : 'Tidak ada billing aktif. Lampu mati.',
        ]);
    }

    /**
     * Status lampu semua meja sekaligus (untuk kontroler pusat).
     *
     * GET /api/microcontroller/tables/light
     */
    public function statusAll(): JsonResponse
    {
        $tables = Table::where('is_active', true)->get();

        $data = $tables->map(fn ($table) => [
            'table_id'      => $table->id,
            'table_name'    => $table->name,
            'light_on'      => (bool) $table->device_status,
        ]);

        return response()->json(['data' => $data]);
    }
}
