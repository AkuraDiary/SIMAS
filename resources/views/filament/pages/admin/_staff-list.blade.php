{{--
    Staff list modal content for a single UnitKerja.
    Variables:
      $unit      – UnitKerja (with jenisUnit, jabatans relations loaded)
      $staffList – Collection<UserPegawaiJabatan> (with pegawai.user, jabatan eager-loaded)
                   ordered by jabatan.level_jabatan ASC (1 = most senior)
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

    $accentBg   = $isAkademis ? 'bg-emerald-100 dark:bg-emerald-900/30' : ($isAdmin ? 'bg-indigo-100 dark:bg-indigo-900/30' : 'bg-slate-100 dark:bg-slate-900/30');
    $accentText = $isAkademis ? 'text-emerald-800 dark:text-emerald-300' : ($isAdmin ? 'text-indigo-800 dark:text-indigo-300' : 'text-slate-800 dark:text-slate-300');
    $accentDotBg= $isAkademis ? 'bg-emerald-500/20 dark:bg-emerald-500/40' : ($isAdmin ? 'bg-indigo-500/20 dark:bg-indigo-500/40' : 'bg-slate-500/20 dark:bg-slate-500/40');
    $accentDotText = $isAkademis ? 'text-emerald-600 dark:text-emerald-400' : ($isAdmin ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400');
@endphp

{{-- ── Unit header ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-3.5 p-3.5 rounded-xl mb-5 {{ $accentBg }}">
    {{-- Jenis badge --}}
    <div class="flex items-center justify-center w-11 h-11 shrink-0 rounded-lg {{ $accentDotBg }} {{ $accentDotText }}">
        @if ($isAkademis)
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M12 3 1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
            </svg>
        @elseif ($isAdmin)
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                <path d="M2 14C2 11.19 2 9.79 2.67 8.78A3.75 3.75 0 0 1 3.78 7.67C4.79 7 6.19 7 9 7h6c2.81 0 4.21 0 5.22.67a3.75 3.75 0 0 1 1.11 1.11C22 9.79 22 11.19 22 14c0 2.81 0 4.21-.68 5.22a3.75 3.75 0 0 1-1.1 1.11C19.21 21 17.81 21 15 21H9c-2.81 0-4.21 0-5.22-.67a3.75 3.75 0 0 1-1.11-1.11C2 18.21 2 16.81 2 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 7c0-1.89 0-2.83-.59-3.41C14.83 3 13.89 3 12 3c-1.89 0-2.83 0-3.41.59C8 4.17 8 5.11 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 11l.65.2C10.09 12.27 13.91 12.27 17.35 11.2L18 11M12 12v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @else
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
            </svg>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <p class="text-[0.7rem] font-bold uppercase tracking-wider opacity-70 mb-0.5 {{ $accentText }}">
            {{ $unit->jenisUnit?->nama_jenis ?? 'Unit Kerja' }}
        </p>
        <p class="text-base font-bold m-0 leading-tight {{ $accentText }}">
            {{ $unit->nama_unit }}
        </p>
        <p class="text-[0.7rem] opacity-65 mt-0.5 mb-0 {{ $accentText }}">
            {{ $unit->singkatan }}
        </p>
    </div>

    {{-- Staff count pill --}}
    <div class="flex flex-col items-center justify-center px-3 py-1.5 rounded-lg shrink-0 text-center {{ $accentDotBg }} {{ $accentText }}">
        <span class="text-xl font-extrabold leading-none">{{ $totalStaff }}</span>
        <span class="text-[0.6rem] font-semibold uppercase tracking-wider opacity-70">Staf</span>
    </div>
</div>

{{-- ── Jabatan count chips ──────────────────────────────────────────────────── --}}
@if ($unit->jabatans->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach ($unit->jabatans->sortBy('level_jabatan') as $jab)
            @php
                $assignedCount = $staffList->filter(fn($r) => $r->jabatan_id === $jab->id)->count();
                $chipBg = $assignedCount > 0 ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900';
                $countColor = $assignedCount > 0 ? 'text-primary-500' : 'text-gray-300 dark:text-gray-600';
            @endphp
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border border-gray-200 dark:border-gray-700 text-[0.7rem] font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap {{ $chipBg }}">
                @if ($jab->level_jabatan)
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-[0.6rem] font-bold text-gray-500 dark:text-gray-400">{{ $jab->level_jabatan }}</span>
                @endif
                {{ $jab->nama_jabatan }}
                <span class="font-bold {{ $countColor }}">{{ $assignedCount }}</span>
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
    <div class="flex items-center gap-2 my-3.5">
        @if ($level)
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 text-[0.65rem] font-bold text-gray-500 dark:text-gray-400 shrink-0">{{ $level }}</span>
        @endif
        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
            {{ $jabatanName }}
        </span>
        <div class="flex-1 h-px bg-gray-100 dark:bg-gray-800"></div>
        <span class="text-[0.65rem] text-gray-400 dark:text-gray-500">{{ $rows->count() }} orang</span>
    </div>

    {{-- Staff cards in this group --}}
    @foreach ($rows as $assignment)
        @php
            $initials = mb_strtoupper(mb_substr($assignment->pegawai?->nama_lengkap ?? '?', 0, 2));
            $email    = $assignment->pegawai?->user?->email ?? null;
            $nip      = $assignment->pegawai?->nip ?? null;

            // Deterministic avatar class from name
            $colors = [
                'bg-indigo-500 text-white', 'bg-purple-500 text-white', 'bg-pink-500 text-white', 
                'bg-teal-500 text-white', 'bg-amber-500 text-white', 'bg-emerald-500 text-white', 'bg-blue-500 text-white'
            ];
            $colorIdx = array_sum(array_map('ord', str_split(substr($assignment->pegawai?->nama_lengkap ?? 'A', 0, 3)))) % count($colors);
            $avatarClass = $colors[$colorIdx];
        @endphp

        <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 mb-1.5 transition-colors hover:border-gray-200 dark:hover:border-gray-700">
            {{-- Avatar --}}
            <div class="flex items-center justify-center w-9 h-9 shrink-0 rounded-full text-xs font-bold tracking-wide {{ $avatarClass }}">
                {{ $initials }}
            </div>

            {{-- Info --}}
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 m-0 leading-tight whitespace-nowrap overflow-hidden text-ellipsis">
                    {{ $assignment->pegawai?->nama_lengkap ?? '—' }}
                </p>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    @if ($nip)
                        <span class="text-[0.7rem] text-gray-500 dark:text-gray-400 font-mono">{{ $nip }}</span>
                    @endif
                    @if ($email)
                        <span class="text-[0.7rem] text-gray-400 dark:text-gray-500">·</span>
                        <span class="text-[0.7rem] text-gray-400 dark:text-gray-500 whitespace-nowrap overflow-hidden text-ellipsis max-w-[12rem]">{{ $email }}</span>
                    @endif
                </div>
            </div>

            {{-- Level badge --}}
            @if ($level)
                <span class="inline-flex items-center justify-center w-6 h-6 shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-[0.65rem] font-extrabold">L{{ $level }}</span>
            @endif
        </div>
    @endforeach

@empty

    {{-- Empty state --}}
    <div class="flex flex-col items-center gap-3 py-10 px-4 text-gray-300 dark:text-gray-600">
        <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
        </svg>
        <p class="text-sm text-gray-400 dark:text-gray-500 m-0">Belum ada staf aktif di unit ini.</p>
        @if ($unit->jabatans->isEmpty())
            <p class="text-xs text-gray-300 dark:text-gray-600 m-0 text-center">Tambahkan jabatan ke unit ini terlebih dahulu melalui Edit Unit.</p>
        @endif
    </div>

@endforelse
