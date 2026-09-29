<?php

use App\Models\Billing;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[On('echo:billiard-updates,BillingTimeExpired')]
    public function onBillingTimeExpired(array $event): void
    {
        $this->dispatch('notify', [
            'message' => $event['message'] ?? 'Waktu sewa meja telah habis. Lampu meja telah dimatikan.',
            'type'    => 'warning',
        ]);

        $this->dispatch('billing-updated');
    }

    public function checkEndingSoon(): void
    {
        $now = now();
        $tenMinsLater = now()->addMinutes(10);

        // Notify for billings ending soon
        $soonBillings = Billing::where('status', 'active')
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '>', $now)
            ->where('scheduled_end_at', '<=', $tenMinsLater->copy()->addMinute())
            ->with('table')
            ->get();

        foreach ($soonBillings as $billing) {
            $diffMins = (int) $now->diffInMinutes($billing->scheduled_end_at, false);

            if (in_array($diffMins, [1, 5, 10], true)) {
                $tableName = $billing->table?->name ?? ('Meja ' . ($billing->table?->table_number ?? '-'));
                $this->dispatch('notify', [
                    'message' => "Billing {$billing->billing_code} ({$tableName}) akan habis dalam {$diffMins} menit!",
                    'type'    => 'warning',
                ]);
            }
        }
    }
};
?>

<div wire:poll.30s="checkEndingSoon">
    {{-- Silently monitors ending soon billings & listens to expired broadcasts --}}
</div>
