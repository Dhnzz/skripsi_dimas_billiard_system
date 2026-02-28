{{--
    resources/views/livewire/meja-status-public.blade.php

    View untuk Livewire MejaStatusPublic component.
    wire:poll.10000ms di div wrapper akan trigger refreshStatus()
    setiap 10 detik → re-render view ini secara otomatis.
--}}
<div wire:poll.10000ms="refreshStatus" id="meja-grid-wrapper">

    {{-- ── Empty state ─────────────────────────────────── --}}
    @if ($tables->isEmpty())
        <div
            style="
        text-align: center;
        padding: 5rem 2rem;
        color: var(--text-muted);
        border: 1px dashed var(--border);
        border-radius: 12px;
    ">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;">🎱</div>
            <div style="font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; letter-spacing: 0.1em;">
                BELUM ADA MEJA TERDAFTAR
            </div>
            <div style="font-size: 0.85rem; margin-top: 0.5rem;">
                Hubungi admin untuk mengatur meja.
            </div>
        </div>
    @else
        {{-- ── Meja Cards Grid ─────────────────────────────── --}}
        <div class="meja-grid">
            @foreach ($tables as $meja)
                <div class="meja-card {{ $meja['status'] }}" wire:key="meja-{{ $meja['id'] }}">

                    {{-- Header: Nomor meja + Status pill --}}
                    <div class="meja-card-header">
                        <div>
                            <div
                                style="
                        font-family: 'JetBrains Mono', monospace;
                        font-size: 0.65rem;
                        letter-spacing: 0.2em;
                        text-transform: uppercase;
                        color: var(--text-muted);
                        margin-bottom: 0.2rem;
                    ">
                                MEJA</div>
                            <div class="meja-number">{{ $meja['table_number'] }}</div>
                        </div>

                        <div class="status-pill {{ $meja['status'] }}">
                            <span class="pill-dot"></span>
                            @if ($meja['status'] === 'available')
                                Tersedia
                            @elseif($meja['status'] === 'occupied')
                                Terpakai
                            @else
                                Maintenance
                            @endif
                        </div>
                    </div>

                    {{-- Nama & deskripsi meja --}}
                    <div class="meja-name">{{ $meja['name'] }}</div>
                    @if ($meja['description'])
                        <div class="meja-desc">{{ $meja['description'] }}</div>
                    @endif

                    <div class="card-divider"></div>

                    {{-- ═══ OCCUPIED STATE ══════════════════════════ --}}
                    @if ($meja['status'] === 'occupied' && $meja['billing'])
                        @php $billing = $meja['billing']; @endphp

                        <div class="billing-info">
                            <div class="billing-info-label">Informasi Sesi Berjalan</div>

                            {{-- Paket + inisial player --}}
                            <div
                                style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin-bottom: 0.75rem;
                ">
                                <div
                                    style="
                        width: 28px; height: 28px;
                        border-radius: 50%;
                        background: rgba(255,68,68,0.15);
                        border: 1px solid rgba(255,68,68,0.25);
                        display: flex; align-items: center; justify-content: center;
                        font-family: 'Bebas Neue', sans-serif;
                        font-size: 0.9rem;
                        color: var(--red);
                    ">
                                    {{ $billing['customer_initial'] }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-dim);">
                                    {{ $billing['package_name'] }}
                                </div>
                            </div>

                            {{-- Waktu mulai --}}
                            <div
                                style="
                    display: flex;
                    justify-content: space-between;
                    font-size: 0.75rem;
                    color: var(--text-muted);
                    font-family: 'JetBrains Mono', monospace;
                    margin-bottom: 0.6rem;
                ">
                                <span>Mulai: {{ \Carbon\Carbon::parse($billing['started_at'])->format('H:i') }}</span>
                                <span>Berjalan: {{ $billing['elapsed_label'] }}</span>
                            </div>

                            {{-- Waktu selesai --}}
                            @if (!$billing['is_loss'] && $billing['end_time_label'])
                                <div class="billing-time-row">
                                    <div>
                                        <div
                                            style="font-size: 0.65rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace; letter-spacing: 0.1em; text-transform: uppercase;">
                                            Selesai Pukul
                                        </div>
                                        <div class="billing-end-time">{{ $billing['end_time_label'] }}</div>
                                    </div>

                                    <div style="text-align: right;">
                                        @if ($billing['remaining_minutes'] > 0)
                                            <div class="billing-countdown">
                                                <span class="billing-countdown-icon">⏱</span>
                                                @if ($billing['remaining_minutes'] >= 60)
                                                    {{ floor($billing['remaining_minutes'] / 60) }}j
                                                    {{ $billing['remaining_minutes'] % 60 }}m lagi
                                                @else
                                                    {{ $billing['remaining_minutes'] }}m lagi
                                                @endif
                                            </div>
                                        @else
                                            <div style="font-size: 0.75rem; color: var(--red); font-weight: 600;">
                                                ⚠ Waktu habis
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Progress bar waktu --}}
                                <div class="time-progress">
                                    <div class="time-progress-bar">
                                        <div class="time-progress-fill"
                                            style="width: {{ $billing['progress_percent'] }}%"></div>
                                    </div>
                                    <div class="time-progress-labels">
                                        <span>{{ \Carbon\Carbon::parse($billing['started_at'])->format('H:i') }}</span>
                                        <span>{{ $billing['progress_percent'] }}%</span>
                                        <span>{{ $billing['end_time_label'] }}</span>
                                    </div>
                                </div>
                            @else
                                {{-- Paket loss: tidak ada end time tetap --}}
                                <div
                                    style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.6rem 0.75rem;
                    background: rgba(255,184,48,0.07);
                    border: 1px solid rgba(255,184,48,0.18);
                    border-radius: 6px;
                    margin-top: 0.25rem;
                ">
                                    <span style="font-size: 1rem;">⏳</span>
                                    <div>
                                        <div style="font-size: 0.72rem; color: var(--amber); font-weight: 600;">
                                            Paket Loss — Waktu Terbuka
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem;">
                                            Dihitung di akhir sesi
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        {{-- /billing-info --}}

                        {{-- ═══ AVAILABLE STATE ═════════════════════════ --}}
                    @elseif($meja['status'] === 'available')
                        <div class="available-info">
                            <span class="available-icon">✅</span>
                            <div class="available-text">
                                <strong>Meja siap digunakan.</strong><br>
                                Login untuk melakukan booking.
                            </div>
                        </div>

                        @auth
                            @role('pelanggan')
                                <a href="{{ route('pelanggan.booking.create', ['table' => $meja['id']]) }}"
                                    class="card-book-btn">
                                    + Booking Meja Ini
                                </a>
                            @endrole
                        @else
                            <a href="{{ route('login') }}" class="card-book-btn">
                                Login untuk Booking →
                            </a>
                        @endauth

                        {{-- ═══ MAINTENANCE STATE ═══════════════════════ --}}
                    @else
                        <div class="maintenance-info">
                            <span style="font-size: 1.1rem;">🔧</span>
                            <div class="maintenance-text">
                                Meja sedang dalam perbaikan.
                                Akan segera tersedia kembali.
                            </div>
                        </div>
                    @endif

                </div>
                {{-- /meja-card --}}
            @endforeach
        </div>
        {{-- /meja-grid --}}

    @endif
    {{-- /empty check --}}

    {{-- Last updated timestamp --}}
    <div
        style="
        text-align: right;
        margin-top: 1.5rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.65rem;
        color: var(--text-muted);
        letter-spacing: 0.1em;
    ">
        LAST UPDATED: {{ now()->format('H:i:s') }}
        &nbsp;·&nbsp;
        AUTO REFRESH / 10s
    </div>

</div>
