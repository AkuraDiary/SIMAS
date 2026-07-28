{{--
    Recursive partial — single UnitKerja node.
    Variables: $unit (array), $isRoot (bool)
--}}
@php
$jenis = strtolower($unit['jenis_unit'] ?? '');
$isAkademis = str_contains($jenis, 'akademik') || str_contains($jenis, 'akademis') || str_contains($jenis, 'fakultas');
$isAdministrasi = str_contains($jenis, 'administrasi');
$isRootNode = $isRoot ?? false;

// Tailwind classes based on type
$cardBorder = $isRootNode 
    ? 'border-2 border-primary-400 dark:border-primary-500 shadow-[0_0_0_3px_rgba(129,140,248,0.2)] dark:shadow-[0_0_0_3px_rgba(99,102,241,0.2)]'
    : 'border border-gray-200 dark:border-gray-700 shadow-sm';

$cardOpacity = $unit['is_active'] ? '' : 'opacity-50';

$typeColor = $isAkademis
    ? 'text-emerald-600 dark:text-emerald-400'
    : ($isAdministrasi ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500');

$typeIcon = $isAkademis ? 'school-o' : ($isAdministrasi ? 'work' : 'o-building-office-2');
@endphp

<div class="flex flex-col items-center">

    {{-- ── "TOP LEVEL UNIT" pill badge ── --}}
    @if ($isRootNode)
    <span class="inline-flex items-center mb-2 px-3 py-0.5 rounded-full bg-primary-600 text-white text-[9px] font-bold uppercase tracking-widest whitespace-nowrap shadow-sm">
        Top Level Unit
    </span>
    @endif

    {{-- ── Node Card ── --}}
    <div class="relative flex flex-col w-44 rounded-xl bg-white dark:bg-gray-800 overflow-hidden transition-all duration-500 ease-out hover:scale-105 hover:brightness-110 dark:hover:brightness-125 {{ $cardBorder }} {{ $cardOpacity }}"
        wire:click="mountAction('viewStaff',  { unitId: {{ $unit['id'] }} })" >
        <div class="p-3.5">

            {{-- Row 1 — jenis label + type icon --}}
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-[9px] font-bold uppercase tracking-widest leading-none whitespace-nowrap overflow-hidden text-ellipsis {{ $typeColor }}">
                    {{ $unit['jenis_unit'] }}
                </span>
                <span class="inline-flex w-4 h-4 shrink-0 overflow-hidden text-gray-400 dark:text-gray-500">
                    <x-icon :name="$typeIcon" class="w-full h-full" />
                </span>
            </div>

            {{-- Row 2 — Unit name --}}
            <p class="text-sm font-semibold text-gray-900 dark:text-white leading-snug m-0 mb-1 line-clamp-2">
                {{ $unit['nama_unit'] }}
            </p>

            {{-- Row 3 — Abbreviation --}}
            <p class="text-[0.7rem] text-gray-400 dark:text-gray-500 m-0 mb-2.5 leading-none">
                {{ $unit['singkatan'] }}
            </p>

            {{-- Divider --}}
            <div class="border-t border-gray-100 dark:border-gray-700 mx-[-0.25rem] mb-2"></div>

            {{-- Footer — staff count | actions --}}
            <div class="flex items-center justify-between">

                {{-- Staff count (employee-group-solid asset) --}}
                <div class="flex items-center gap-1 text-gray-400 dark:text-gray-500 text-xs">
                    <span class="inline-flex w-3.5 h-3.5 shrink-0 overflow-hidden">
                        <x-icon name="employee-group-solid" class="w-full h-full" />
                    </span>
                    <span>{{ $unit['staff_count'] }}</span>
                </div>

                {{-- Filament icon-buttons (CSS from Filament bundle — not affected by JIT) --}}
                <div class="flex items-center mr-[-0.5rem]">
                    <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="sm" tooltip="Hapus Unit" wire:click.stop="mountAction('deleteUnit', { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button icon="heroicon-o-pencil-square" color="warning" size="sm" tooltip="Edit Unit" wire:click.stop="mountAction('editUnit',   { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button icon="heroicon-o-user-group" color="primary" size="sm" tooltip="Lihat Staf" wire:click.stop="mountAction('viewStaff',  { unitId: {{ $unit['id'] }} })" />
                </div>
            </div>
        </div>
    </div>
    {{-- /Node Card --}}

    {{-- ── Tree branches ── --}}
    @if (!empty($unit['children']))

    <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 shrink-0"></div>

    {{-- Dashed children group container --}}
    <div class="flex items-start border-[1.5px] border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-3 gap-0">

        @foreach ($unit['children'] as $child)
        <div class="flex flex-col items-center px-3">
            <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 shrink-0"></div>
            @include('filament.pages.admin._unit-node', ['unit' => $child, 'isRoot' => false])
        </div>
        @endforeach

        {{-- "+ Tambah Unit" placeholder (plus.svg asset) --}}
        <div class="flex flex-col items-center px-3">
            <div class="w-px h-5 bg-transparent shrink-0"></div>
            <button
                type="button"
                wire:click.stop="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
                class="flex flex-col items-center justify-center gap-1.5 w-44 min-h-[7.5rem] rounded-xl border-2 border-dashed border-primary-300 dark:border-primary-700 bg-primary-50/60 dark:bg-primary-900/20 text-primary-400 dark:text-primary-500 text-xs font-medium cursor-pointer transition-colors hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-500 dark:hover:text-primary-400">
                <span class="inline-flex w-4 h-4 overflow-hidden">
                    <x-icon name="plus" class="w-full h-full" />
                </span>
                Tambah Unit
            </button>
        </div>
    </div>

    @else

    {{-- Leaf: "+ Tambah Sub-Unit" (plus.svg asset) --}}
    <button
        type="button"
        wire:click.stop="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
        class="flex items-center justify-center gap-1.5 mt-1.5 w-44 py-1.5 rounded-lg border border-dashed border-primary-400 dark:border-primary-600 bg-transparent text-primary-500 dark:text-primary-400 text-xs font-medium cursor-pointer transition-colors hover:border-primary-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30">
        <span class="inline-flex w-3 h-3 overflow-hidden shrink-0">
            <x-icon name="plus" class="w-full h-full" />
        </span>
        Buat Unit Dibawahnya
    </button>

    @endif

</div>
