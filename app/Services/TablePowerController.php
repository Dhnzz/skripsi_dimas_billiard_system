<?php

namespace App\Services;

use App\Events\TableStatusUpdated;
use App\Models\Table;
use Illuminate\Support\Facades\Log;

class TablePowerController
{
    /**
     * Nyalakan lampu meja (power on) dan broadcast status ke ekosistem.
     *
     * @param Table|int $table
     * @param string $reason
     * @return Table
     */
    public function turnOn(Table|int $table, string $reason = 'manual'): Table
    {
        $tableModel = $table instanceof Table ? $table : Table::findOrFail($table);

        if (!$tableModel->device_status) {
            $tableModel->update(['device_status' => true]);
        }

        $this->dispatchTableUpdate($tableModel->id, true, $reason);

        return $tableModel;
    }

    /**
     * Padamkan lampu meja (power off) dan broadcast status ke ekosistem.
     *
     * @param Table|int $table
     * @param string $reason
     * @return Table
     */
    public function turnOff(Table|int $table, string $reason = 'manual'): Table
    {
        $tableModel = $table instanceof Table ? $table : Table::findOrFail($table);

        if ($tableModel->device_status) {
            $tableModel->update(['device_status' => false]);
        }

        $this->dispatchTableUpdate($tableModel->id, false, $reason);

        return $tableModel;
    }

    /**
     * Dispatch event TableStatusUpdated dengan perlindungan try-catch.
     */
    protected function dispatchTableUpdate(int $tableId, bool $status, string $reason): void
    {
        try {
            broadcast(new TableStatusUpdated($tableId));
        } catch (\Throwable $e) {
            Log::warning("[TablePowerController] Broadcast failed for Table #{$tableId} ({$reason}): " . $e->getMessage());
        }
    }
}
