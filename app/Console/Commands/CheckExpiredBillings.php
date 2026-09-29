<?php

namespace App\Console\Commands;

use App\Events\BillingTimeExpired;
use App\Events\BillingUpdated;
use App\Events\TableStatusUpdated;
use App\Models\Billing;
use App\Models\User;
use App\Notifications\BillingTimeExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckExpiredBillings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Matikan lampu meja saat waktu billing habis dan kirim notifikasi ke kasir/owner';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredBillings = Billing::query()
            ->where('status', 'active')
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '<=', now())
            ->whereHas('table', fn ($q) => $q->where('device_status', true))
            ->with('table')
            ->get();

        if ($expiredBillings->isEmpty()) {
            return self::SUCCESS;
        }

        $recipients = User::role(['kasir', 'owner'])->where('is_active', true)->get();

        foreach ($expiredBillings as $billing) {
            DB::transaction(function () use ($billing) {
                $billing->table->update(['device_status' => false]);
            });

            try {
                broadcast(new TableStatusUpdated($billing->table_id));
                broadcast(new BillingUpdated($billing->id));
                broadcast(new BillingTimeExpired($billing));
            } catch (\Throwable $e) {
                report($e);
            }

            if ($recipients->isNotEmpty()) {
                try {
                    Notification::send($recipients, new BillingTimeExpiredNotification($billing));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->info("Processed {$expiredBillings->count()} expired billing(s).");

        return self::SUCCESS;
    }
}
