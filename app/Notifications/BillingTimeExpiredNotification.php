<?php

namespace App\Notifications;

use App\Models\Billing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingTimeExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(public Billing $billing)
    {
    }

    public function via(object $notifiable): array
    {
        // ponytail: mail channel default; add database/broadcast when table is migrated
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tableName = $this->billing->table?->name ?? ('Meja ' . ($this->billing->table?->table_number ?? ''));

        return (new MailMessage)
            ->subject("Waktu Bermain Habis: {$tableName}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Waktu bermain untuk {$tableName} (Billing #{$this->billing->billing_code}) telah habis.")
            ->line("Lampu meja telah otomatis dimatikan oleh sistem.")
            ->line("Lampu akan tetap padam sampai perpanjangan waktu dilakukan atau billing diselesaikan oleh kasir.")
            ->action('Lihat Detail Billing', url('/billing/' . $this->billing->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'billing_id'   => $this->billing->id,
            'billing_code' => $this->billing->billing_code,
            'table_id'     => $this->billing->table_id,
            'table_name'   => $this->billing->table?->name,
            'message'      => "Waktu bermain di {$this->billing->table?->name} telah habis. Lampu meja dimatikan.",
        ];
    }
}
