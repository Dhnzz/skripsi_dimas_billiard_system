# Implementation Plan: Fix Lamp Not Turning Off on Expired & Force Stopped Billiards

Berdasarkan disiplin debugging **Matt Pocock (`diagnosing-bugs`)**, rencana implementasi ini dibagi ke dalam fase berurutan yang memiliki feedback loop ketat (tight pass/fail signal) sebelum dan sesudah perubahan kode diterapkan.

---

## 1. Problem Statement & Root Cause Mapping
* **Symptom 1**: Saat batas waktu sewa meja billiard habis (*timed out*), lampu meja tidak mati jika kasir/owner tidak sedang membuka halaman monitor di browser.
  * **Root Cause**: Logika pengecekan expired sebelumnya terpasang di Livewire component (`wire:poll.10s`), bukan di background cron/scheduler server.
* **Symptom 2**: Saat kasir/admin mematikan paksa sewa meja (*force stop / finish*), lampu pada fisik meja tetap menyala.
  * **Root Cause (Firmware)**: ESP32 melakukan HTTP GET ke `https://...` tanpa konfigurasi `WiFiClientSecure` (`client.setInsecure()`). Akibatnya TLS/SSL handshake gagal (error code -1) dan perintah `digitalWrite` tidak pernah dieksekusi.
  * **Root Cause (Hardware Polarity)**: Modul relay 5V Arduino umumnya beroperasi sebagai **Active LOW** (sinyal `LOW` = relay hidup, `HIGH` = relay mati). Skrip lama hanya mendukung hardcoded Active HIGH.
  * **Root Cause (Inefficient Polling)**: Skrip lama melakukan 10 request HTTP berurutan tiap 5 detik (1 request per meja), yang membebani TCP stack ESP32 dan berisiko timeout.

---

## 2. Phase 1: Feedback Loop (Pass/Fail Verification Seams)

Sebelum dan sesudah implementasi, setiap langkah akan divalidasi dengan satu perintah deterministik:

1. **Seam A (Backend Expired Checker)**:
   * **Test Command**:
     ```bash
     docker exec billiard-php php artisan test --filter=CheckExpiredBillingsTest
     ```
   * **Sinyal Lulus**: Semua unit/feature test untuk command `billing:check-expired` berstatus hijau (PASS).
2. **Seam B (Scheduler Verification)**:
   * **Test Command**:
     ```bash
     docker exec billiard-php php artisan schedule:list
     ```
   * **Sinyal Lulus**: Menampilkan task `billing:check-expired` terdaftar dengan interval `everyMinute`.
3. **Seam C (Microcontroller Batch API Endpoint)**:
   * **Test Command**:
     ```bash
     curl -s -k https://billiard-system.azhr.cloud/api/microcontroller/tables/light
     ```
   * **Sinyal Lulus**: Mengembalikan format JSON batch status 10 meja (`light_on: true/false`) dengan status HTTP `200 OK`.
4. **Seam D (Simulation of Force Stop)**:
   * **Scenario**:
     * Aktifkan billing meja (status `device_status = 1`).
     * Panggil `finish()` / batalkan billing.
     * Query API microcontroller: `light_on` harus langsung berubah menjadi `false`.

---

## 3. Phase 2: Detailed Implementation Steps

### Step 1: Sinkronisasi Backend dengan Commit GitHub Terbaru
1. Simpan perubahan deployment lokal (`git stash` / stash `.gitignore` tweaks).
2. Merge commit backend terbaru dari `origin/main` (`git merge origin/main --no-edit`).
3. Verifikasi file yang diperbarui:
   * `app/Console/Commands/CheckExpiredBillings.php` (Command cron mematikan lampu meja yang expired)
   * `routes/console.php` (Jadwal `Schedule::command('billing:check-expired')->everyMinute()`)
   * `app/Events/TableStatusUpdated.php` & `app/Events/BillingTimeExpired.php`
   * `resources/views/livewire/billing-monitor.blade.php` (Refaktor dari auto-finish ke pure notification monitor)
4. Bersihkan dan perbarui cache Laravel:
   ```bash
   docker exec billiard-php php artisan optimize:clear
   docker exec billiard-php php artisan config:cache
   docker exec billiard-php php artisan route:cache
   ```
5. Restart container `billiard-scheduler` agar memuat jadwal `billing:check-expired` yang baru:
   ```bash
   docker compose -f /root/skripsi_dimas_billiard_system/.deploy/docker-compose.yml restart scheduler
   ```

### Step 2: Optimasi & Perbaikan Firmware Arduino / ESP32
Menyediakan firmware Arduino yang **bulletproof** untuk HTTP Polling 5 Detik:
1. **Dukungan SSL/TLS Cloudflare**:
   * Gunakan `WiFiClientSecure client; client.setInsecure();` agar request ke `https://billiard-system.azhr.cloud` sukses 100% tanpa ditolak oleh sertifikat SSL.
2. **Peralihan ke Batch Endpoint**:
   * Ganti 10 request per-meja (`/api/microcontroller/table/{id}/light`) menjadi **1 request batch** ke `/api/microcontroller/tables/light`.
   * Mengurangi beban koneksi, mencegah freeze pada ESP32, dan menjamin respons sinkron dalam hitungan milidetik.
3. **Konfigurasi Polaritas Relay Fleksibel**:
   * Tambahkan parameter konfigurable di baris atas sketch:
     ```cpp
     const bool RELAY_ACTIVE_HIGH = false; // Set false untuk modul relay Active LOW (umum), true jika Active HIGH
     ```
   * Update fungsi `updateRelay()` agar mematikan pin relay secara tepat sesuai jenis modul fisik yang terpasang.
4. **Interval Polling**:
   * Ditetapkan pada `5000 ms` (5 detik) sesuai spesifikasi yang diminta pengguna.

---

## 4. Phase 3: Rollback & Risk Mitigation Strategy
* **Risiko**: Scheduler gagal berjalan atau database terkunci.
  * **Mitigasi**: Pengecekan status container via `docker logs billiard-scheduler`.
* **Risiko**: Modul relay pelanggan bertipe Active HIGH sementara default Active LOW.
  * **Mitigasi**: Dokumentasikan opsi `RELAY_ACTIVE_HIGH = true/false` di header file `.ino` dan output Serial Monitor agar teknisi di lokasi dapat langsung menyesuaikannya jika lampu terbalik (hidup saat harus mati).

---

## 5. Phase 4: Output Deliverables
1. Backend ter-update dan scheduler aktif memeriksa waktu habis per menit.
2. File firmware Arduino siap pakai (`billiardLaravelArduino/billiardLaravelArduino.ino`) yang dapat langsung di-upload teknisi ke ESP32.
3. Laporan verifikasi pengujian otomatis (seam check).
