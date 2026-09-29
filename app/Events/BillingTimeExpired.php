<?php

namespace App\Events;

use App\Models\Billing;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingTimeExpired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $billingId;
    public ?int $tableId;
    public ?string $tableName;
    public string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Billing $billing)
    {
        $this->billingId = $billing->id;
        $this->tableId   = $billing->table_id;
        $this->tableName = $billing->table?->name ?? ('Meja ' . ($billing->table?->table_number ?? ''));
        $this->message   = "Waktu bermain di {$this->tableName} telah habis. Lampu meja dimatikan.";
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
}
