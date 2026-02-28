<?php

namespace App\Livewire;

use App\Models\Billing;
use App\Models\Table;
use Livewire\Component;

class TableStatusPublic extends Component
{
    /**
     * Dipanggil otomatis oleh wire:poll setiap 10 detik.
     * Tidak perlu isi body — Livewire akan re-render
     * komponen secara otomatis setiap kali method ini dipanggil.
     */
    public function refreshStatus(): void
    {
        // Kosong — re-render otomatis terjadi karena method dipanggil
    }

    public function render()
    {
        // Ambil semua meja aktif beserta billing yang sedang berjalan
        $tables = Table::where('is_active', true)
            ->orderBy('table_number')
            ->with([
                // Load billing yang sedang aktif saja (eager loading)
                'billing' => function ($query) {
                    $query->with(['customer', 'package'])->where('status', 'active');
                },
            ])
            ->get()
            ->map(function ($table) {
                // Hitung data tambahan untuk setiap meja
                $billing = $table->activeBilling;

                return [
                    'id'           => $table->id,
                    'table_number' => $table->table_number,
                    'name'         => $table->name,
                    'description'  => $table->description,
                    'status'       => $table->status,

                    // Data billing jika meja sedang occupied
                    'billing' => $billing ? [
                        'id'               => $billing->id,
                        'started_at'       => $billing->started_at,
                        'scheduled_end_at' => $billing->scheduled_end_at,

                        // Waktu selesai yang diformat (HH:MM)
                        'end_time_label'   => $billing->scheduled_end_at
                            ? $billing->scheduled_end_at->format('H:i')
                            : null,

                        // Hitung sisa waktu (dalam menit)
                        'remaining_minutes' => $billing->scheduled_end_at
                            ? max(0, (int) now()->diffInMinutes($billing->scheduled_end_at, false))
                            : null,

                        // Apakah billing ini paket loss (tidak ada end time fix)
                        'is_loss'          => $billing->package?->isLoss() || $billing->package === null,

                        // Persentase waktu yang sudah dipakai (untuk progress bar)
                        // Hanya relevan untuk paket normal/ada scheduled_end_at
                        'progress_percent' => $this->calcProgress($billing),

                        // Durasi berjalan (format HH:MM:SS)
                        'elapsed_label'    => gmdate('H:i', (int) $billing->started_at->diffInSeconds(now())),

                        // Nama customer (hanya inisial untuk privasi)
                        'customer_initial' => $billing->customer
                            ? strtoupper(substr($billing->customer->name, 0, 1))
                            : '?',

                        // Nama paket
                        'package_name'     => $billing->package?->name ?? 'Tanpa Paket',
                    ] : null,
                ];
            });

        return view('livewire.table-status-public', compact('tables'));
    }

    /**
     * Hitung persentase waktu yang sudah berjalan.
     * Digunakan untuk progress bar di card occupied.
     */
    private function calcProgress(Billing $billing): float
    {
        if (!$billing->scheduled_end_at) {
            // Paket loss: gunakan jam berjalan vs asumsi 3 jam sebagai referensi
            $totalSeconds   = 3 * 3600;
            $elapsedSeconds = min($billing->started_at->diffInSeconds(now()), $totalSeconds);
            return round(($elapsedSeconds / $totalSeconds) * 100, 1);
        }

        $totalDuration   = $billing->started_at->diffInSeconds($billing->scheduled_end_at);
        $elapsedDuration = $billing->started_at->diffInSeconds(now());

        if ($totalDuration <= 0) return 100;

        return min(100, round(($elapsedDuration / $totalDuration) * 100, 1));
    }
}
