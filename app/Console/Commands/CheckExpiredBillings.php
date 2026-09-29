<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BillingTimeExpiredNotification;
use App\Services\BillingSessionManager;
use Illuminate\Console\Command;
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
    public function handle(BillingSessionManager $billingSessionManager): int
    {
        $expiredBillings = $billingSessionManager->expireOverdueSessions();

        if ($expiredBillings->isEmpty()) {
            return self::SUCCESS;
        }

        $recipients = User::role(['kasir', 'owner'])->where('is_active', true)->get();

        if ($recipients->isNotEmpty()) {
            foreach ($expiredBillings as $billing) {
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
