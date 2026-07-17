{{--
    Staff list modal content for a single UnitKerja.
    Variables:
      $unit      – UnitKerja (with jenisUnit, jabatans relations loaded)
      $staffList – Collection<UserPegawaiJabatan> (with pegawai.user, jabatan eager-loaded)
                   ordered by jabatan.level_jabatan ASC (1 = most senior)

    Styling: mix of Filament classes (in modal context, not canvas — JIT is fine here)
    and inline styles for precision where needed.
--}}

@php
    // Group by jabatan.level_jabatan → jabatan.nama_jabatan → staff rows
    $grouped = $staffList->groupBy(fn($row) =>
        ($row->jabatan?->level_jabatan ?? 999) . '||' . ($row->jabatan?->nama_jabatan ?? 'Tanpa Jabatan')
    )->sortKeys();

    $totalStaff = $staffList->count();

    $jenis = strtolower($unit->jenisUnit?->nama_jenis ?? '');
    $isAkademis = str_contains($jenis, 'akademik') || str_contains($jenis, 'akademis') || str_contains($jenis, 'fakultas');
    $isAdmin    = str_contains($jenis, 'administrasi');

    $accentBg   = $isAkademis ? '#d1fae5' : ($isAdmin ? '#e0e7ff' : '#f1f5f9');
    $accentText = $isAkademis ? '#065f46' : ($isAdmin ? '#3730a3' : '#334155');
    $accentDot  = $isAkademis ? '#10b981' : ($isAdmin ? '#6366f1' : '#64748b');
@endphp

{{-- ── Unit header ──────────────────────────────────────────────────────────── --}}
<div style="
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.875rem 1rem;
    background: {{ $accentBg }};
    border-radius: 0.625rem;
    margin-bottom: 1.25rem;
">
    {{-- Jenis badge --}}
    <div style="
        display: flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem; flex-shrink: 0;
        border-radius: 0.5rem;
        background: {{ $accentDot }}22;
        color: {{ $accentDot }};
    ">
        @if ($isAkademis)
            <svg style="width:1.5rem;height:1.5rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M12 3 1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
            </svg>
        @elseif ($isAdmin)
            <svg style="width:1.5rem;height:1.5rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                <path d="M2 14C2 11.19 2 9.79 2.67 8.78A3.75 3.75 0 0 1 3.78 7.67C4.79 7 6.19 7 9 7h6c2.81 0 4.21 0 5.22.67a3.75 3.75 0 0 1 1.11 1.11C22 9.79 22 11.19 22 14c0 2.81 0 4.21-.68 5.22a3.75 3.75 0 0 1-1.1 1.11C19.21 21 17.81 21 15 21H9c-2.81 0-4.21 0-5.22-.67a3.75 3.75 0 0 1-1.11-1.11C2 18.21 2 16.81 2 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 7c0-1.89 0-2.83-.59-3.41C14.83 3 13.89 3 12 3c-1.89 0-2.83 0-3.41.59C8 4.17 8 5.11 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 11l.65.2C10.09 12.27 13.91 12.27 17.35 11.2L18 11M12 12v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @else
            <svg style="width:1.5rem;height:1.5rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
            </svg>
        @endif
    </div>

    <div style="min-width:0; flex:1;">
        <p style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:{{ $accentText }}; opacity:0.7; margin:0 0 0.15rem;">
            {{ $unit->jenisUnit?->nama_jenis ?? 'Unit Kerja' }}
        </p>
        <p style="font-size:1rem; font-weight:700; color:{{ $accentText }}; margin:0; line-height:1.25;">
            {{ $unit->nama_unit }}
        </p>
        <p style="font-size:0.7rem; color:{{ $accentText }}; opacity:0.65; margin:0.15rem 0 0;">
            {{ $unit->singkatan }}
        </p>
    </div>

    {{-- Staff count pill --}}
    <div style="
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        background: {{ $accentDot }}22;
        color: {{ $accentText }};
        flex-shrink: 0;
        text-align: center;
    ">
        <span style="font-size:1.25rem; font-weight:800; line-height:1;">{{ $totalStaff }}</span>
        <span style="font-size:0.6rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; opacity:0.7;">Staf</span>
    </div>
</div>

{{-- ── Jabatan count chips ──────────────────────────────────────────────────── --}}
@if ($unit->jabatans->isNotEmpty())
    <div style="display:flex; flex-wrap:wrap; gap:0.375rem; margin-bottom:1rem;">
        @foreach ($unit->jabatans->sortBy('level_jabatan') as $jab)
            @php
                $assignedCount = $staffList->filter(fn($r) => $r->jabatan_id === $jab->id)->count();
            @endphp
            <span style="
                display:inline-flex; align-items:center; gap:0.25rem;
                padding: 0.2rem 0.6rem;
                border-radius: 999px;
                border: 1px solid #e5e7eb;
                background: {{ $assignedCount > 0 ? '#f9fafb' : '#fff' }};
                font-size: 0.7rem; font-weight:500; color:#374151;
                white-space:nowrap;
            ">
                @if ($jab->level_jabatan)
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:1rem;height:1rem;border-radius:999px;background:#e5e7eb;font-size:0.6rem;font-weight:700;color:#6b7280;">{{ $jab->level_jabatan }}</span>
                @endif
                {{ $jab->nama_jabatan }}
                <span style="color:{{ $assignedCount > 0 ? '#6366f1' : '#d1d5db' }};font-weight:700;">{{ $assignedCount }}</span>
            </span>
        @endforeach
    </div>
@endif

{{-- ── Staff rows, grouped by jabatan level ───────────────────────────────────── --}}
@forelse ($grouped as $groupKey => $rows)
    @php
        [$level, $jabatanName] = explode('||', $groupKey, 2);
        $level = ($level == 999) ? null : (int) $level;
    @endphp

    {{-- Group header --}}
    <div style="display:flex; align-items:center; gap:0.5rem; margin:0.875rem 0 0.375rem;">
        @if ($level)
            <span style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;border-radius:999px;background:#e5e7eb;font-size:0.65rem;font-weight:700;color:#6b7280;flex-shrink:0;">{{ $level }}</span>
        @endif
        <span style="font-size:0.75rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.06em;">
            {{ $jabatanName }}
        </span>
        <div style="flex:1; height:1px; background:#f3f4f6;"></div>
        <span style="font-size:0.65rem; color:#9ca3af;">{{ $rows->count() }} orang</span>
    </div>

    {{-- Staff cards in this group --}}
    @foreach ($rows as $assignment)
        @php
            $initials = mb_strtoupper(mb_substr($assignment->pegawai?->nama_lengkap ?? '?', 0, 2));
            $email    = $assignment->pegawai?->user?->email ?? null;
            $nip      = $assignment->pegawai?->nip ?? null;

            // Deterministic avatar color from name
            $colors = ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#10b981','#3b82f6'];
            $colorIdx = array_sum(array_map('ord', str_split(substr($assignment->pegawai?->nama_lengkap ?? 'A', 0, 3)))) % count($colors);
            $avatarBg = $colors[$colorIdx];
        @endphp

        <div style="
            display:flex; align-items:center; gap:0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.625rem;
            border: 1px solid #f3f4f6;
            background: #ffffff;
            margin-bottom: 0.375rem;
            transition: border-color 0.15s;
        ">
            {{-- Avatar --}}
            <div style="
                display:flex; align-items:center; justify-content:center;
                width: 2.25rem; height: 2.25rem; flex-shrink: 0;
                border-radius: 999px;
                background: {{ $avatarBg }};
                color: #fff;
                font-size: 0.75rem; font-weight: 700;
                letter-spacing: 0.02em;
            ">{{ $initials }}</div>

            {{-- Info --}}
            <div style="min-width:0; flex:1;">
                <p style="font-size:0.875rem; font-weight:600; color:#111827; margin:0; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $assignment->pegawai?->nama_lengkap ?? '—' }}
                </p>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.15rem; flex-wrap:wrap;">
                    @if ($nip)
                        <span style="font-size:0.7rem; color:#6b7280; font-family:monospace;">{{ $nip }}</span>
                    @endif
                    @if ($email)
                        <span style="font-size:0.7rem; color:#9ca3af;">·</span>
                        <span style="font-size:0.7rem; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:12rem;">{{ $email }}</span>
                    @endif
                </div>
            </div>

            {{-- Level badge --}}
            @if ($level)
                <span style="
                    display:inline-flex; align-items:center; justify-content:center;
                    width: 1.5rem; height: 1.5rem; flex-shrink: 0;
                    border-radius: 999px;
                    background: {{ $avatarBg }}22;
                    color: {{ $avatarBg }};
                    font-size: 0.65rem; font-weight: 800;
                ">L{{ $level }}</span>
            @endif
        </div>
    @endforeach

@empty

    {{-- Empty state --}}
    <div style="display:flex; flex-direction:column; align-items:center; gap:0.75rem; padding:2.5rem 1rem; color:#d1d5db;">
        <svg style="width:3rem;height:3rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
        </svg>
        <p style="font-size:0.875rem; color:#9ca3af; margin:0;">Belum ada staf aktif di unit ini.</p>
        @if ($unit->jabatans->isEmpty())
            <p style="font-size:0.75rem; color:#d1d5db; margin:0;">Tambahkan jabatan ke unit ini terlebih dahulu melalui Edit Unit.</p>
        @endif
    </div>

@endforelse
