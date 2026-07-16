{{--
    Recursive partial — single UnitKerja node.
    Variables: $unit (array), $isRoot (bool)
--}}
@php
$jenis = strtolower($unit['jenis_unit'] ?? '');
$isAkademis = str_contains($jenis, 'akademik')
|| str_contains($jenis, 'akademis')
|| str_contains($jenis, 'fakultas');
$isAdministrasi = str_contains($jenis, 'administrasi');
@endphp

<div class="org-tree-node flex flex-col items-center">

    {{-- ── "TOP LEVEL UNIT" pill — floats above the card ── --}}
    @if ($isRoot ?? false)
    <span class="mb-2 inline-flex items-center rounded-full bg-primary-600 px-3 py-0.5
                     text-[9px] font-bold uppercase tracking-widest text-white shadow-sm whitespace-nowrap">
        Top Level Unit
    </span>
    @endif

    {{-- ── Node Card ── --}}
    <div @class([ 'org-card flex flex-col rounded-xl border bg-white dark:bg-gray-900 shadow-sm w-44 overflow-hidden' , 'border-primary-300 shadow-primary-100/60 dark:shadow-none ring-1 ring-primary-300/50'=> $isRoot ?? false,
        'border-gray-200 dark:border-gray-700' => !($isRoot ?? false),
        'opacity-50' => !$unit['is_active'],
        ])>

        <div class="px-3.5 pt-3 pb-3 flex flex-col gap-2.5">

            {{-- Row 1 — jenis label + type icon --}}
            <div class="flex items-center justify-between gap-2 min-w-0">
                <span class="text-[9px] font-semibold uppercase tracking-widest leading-none
                             text-gray-400 dark:text-gray-500 truncate">
                    {{ $unit['jenis_unit'] }}
                </span>

                {{-- SVG inline so width/height attrs don't fight CSS sizing --}}
                @if ($isAkademis)
                <x-icon name="school-o" />
                @elseif ($isAdministrasi)
                <x-icon name="work" />
                @else
                <x-icon name="o-building-office-2" />
                @endif
            </div>

            {{-- Row 2 — unit name + abbreviation --}}
            <div class="space-y-0.5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-snug line-clamp-2">
                    {{ $unit['nama_unit'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $unit['singkatan'] }}
                </p>
            </div>

            {{-- Divider --}}
            <div class="border-t border-gray-100 dark:border-gray-800 -mx-0.5"></div>

            {{-- Footer — staff count | action buttons --}}
            <div class="flex items-center justify-between -my-0.5">
                <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-users class="h-3.5 w-3.5 shrink-0" />
                    <span>{{ $unit['staff_count'] }}</span>
                </div>

                <div class="flex items-center -mr-2">
                    <x-filament::icon-button
                        icon="heroicon-o-trash"
                        color="danger"
                        size="sm"
                        tooltip="Hapus Unit"
                        wire:click="mountAction('deleteUnit', { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button
                        icon="heroicon-o-pencil-square"
                        color="warning"
                        size="sm"
                        tooltip="Edit Unit"
                        wire:click="mountAction('editUnit', { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button
                        icon="heroicon-o-user-group"
                        color="primary"
                        size="sm"
                        tooltip="Lihat Staf"
                        wire:click="mountAction('viewStaff', { unitId: {{ $unit['id'] }} })" />
                </div>
            </div>
        </div>
    </div>
    {{-- /Node Card --}}

    {{-- ── Tree branches ── --}}
    @if (!empty($unit['children']))

    {{-- Vertical stem: card bottom → container top --}}
    <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 shrink-0"></div>

    {{-- Children group container (dashed rounded box — matches wireframe) --}}
    <div class="org-children-group rounded-xl border border-dashed border-gray-300 dark:border-gray-600
                    p-3 flex items-start gap-0">

        @foreach ($unit['children'] as $child)
        <div class="org-child-slot flex flex-col items-center px-3">
            {{-- Vertical drop from container top to card --}}
            <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 shrink-0"></div>
            @include('filament.pages.admin._unit-node', ['unit' => $child, 'isRoot' => false])
        </div>
        @endforeach

        {{-- "+ Tambah Unit" sibling placeholder (lavender tint, matches wireframe) --}}
        <div class="flex flex-col items-center px-3">
            <div class="w-px h-5 bg-transparent shrink-0"></div>
            <button
                type="button"
                wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
                class="flex flex-col items-center justify-center gap-1.5 rounded-xl
                           border-2 border-dashed border-primary-200 dark:border-primary-800/60
                           bg-primary-50/60 dark:bg-primary-900/10
                           text-primary-400 dark:text-primary-600
                           hover:border-primary-400 hover:text-primary-600 hover:bg-primary-50
                           dark:hover:border-primary-700 dark:hover:text-primary-400
                           transition-colors w-44 min-h-[7.5rem]">
                <x-icon name="plus" />
                <span class="text-xs font-medium">Tambah Unit</span>
            </button>
        </div>
    </div>

    @else

    {{-- Leaf node: "Tambah Sub-Unit" — full card width, primary dashed --}}
    <button
        type="button"
        wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
        class="mt-1.5 flex items-center justify-center gap-1.5 rounded-lg w-44 py-1.5
                   border border-dashed border-primary-300 dark:border-primary-700/60
                   text-primary-500 dark:text-primary-500 text-xs font-medium
                   hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors">
                   <x-icon name="plus" />
        Tambah Sub-Unit
    </button>

    @endif

</div>