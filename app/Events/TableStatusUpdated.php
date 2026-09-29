<?php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId = null)
    {
        $this->tableId = $tableId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('billiard-updates'),
        ];
    }

    /**
     * Data yang dikirim ke WebSocket client (Browser & ESP32/Arduino).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $table = Table::find($this->tableId);

        return [
            'tableId'       => $this->tableId,
            'table_id'      => (int) $this->tableId,
            'table_number'  => $table?->table_number,
            'device_status' => (bool) ($table?->device_status ?? false),
            'light_on'      => (bool) ($table?->device_status ?? false),
            'status'        => $table?->status,
        ];
    }
}
