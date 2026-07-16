{{--
    Recursive partial: renders a single UnitKerja node card + its children.

    Variables:
      $unit   – array node from UnitKerjaService::buildTreeData()
      $isRoot – bool, true only for top-level roots
--}}

<div class="org-tree-node flex flex-col items-center">

    {{-- ── Node Card ──────────────────────────────────────────────────────── --}}
    <div @class([
            'org-card relative flex flex-col gap-2 rounded-xl border bg-white dark:bg-gray-900 shadow-sm p-4 w-56 text-sm',
            'border-primary-400 ring-2 ring-primary-400/40' => $isRoot ?? false,
            'border-gray-200 dark:border-gray-700'          => !($isRoot ?? false),
            'opacity-60'                                    => !$unit['is_active'],
        ])>

        {{-- Top-level badge --}}
        @if ($isRoot ?? false)
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white whitespace-nowrap shadow">
                Top Level
            </span>
        @endif

        {{-- Jenis unit label + icon --}}
        <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-primary-500 dark:text-primary-400 truncate">
                {{ $unit['jenis_unit'] }}
            </span>
            @if(stripos($unit['jenis_unit'], 'akademik') !== false || stripos($unit['jenis_unit'], 'akademis') !== false || stripos($unit['jenis_unit'], 'fakultas') !== false)
                <x-icon name="school-o" class="h-4 w-4 shrink-0 text-gray-400" />
            @elseif(stripos($unit['jenis_unit'], 'administrasi') !== false)
                <x-icon name="work" class="h-4 w-4 shrink-0 text-gray-400" />
            @else
                <x-heroicon-o-building-office-2 class="h-4 w-4 shrink-0 text-gray-400" />
            @endif
        </div>

        {{-- Name & abbreviation --}}
        <div>
            <p class="font-semibold text-gray-900 dark:text-white leading-snug">{{ $unit['nama_unit'] }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $unit['singkatan'] }}</p>
        </div>

        {{-- Footer: staff count + actions --}}
        <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-800">
            {{-- Staff count --}}
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <x-heroicon-o-users class="h-3.5 w-3.5" />
                <span>{{ $unit['staff_count'] }} staf</span>
            </div>

            {{-- Action icon-buttons (Filament style) --}}
            <div class="flex items-center gap-0.5">
                <x-filament::icon-button
                    icon="heroicon-o-user-group"
                    color="primary"
                    size="sm"
                    tooltip="Lihat Staf Aktif"
                    wire:click="mountAction('viewStaff', { unitId: {{ $unit['id'] }} })"
                />
                <x-filament::icon-button
                    icon="heroicon-o-pencil-square"
                    color="warning"
                    size="sm"
                    tooltip="Edit Unit"
                    wire:click="mountAction('editUnit', { unitId: {{ $unit['id'] }} })"
                />
                <x-filament::icon-button
                    icon="heroicon-o-trash"
                    color="danger"
                    size="sm"
                    tooltip="Hapus Unit"
                    wire:click="mountAction('deleteUnit', { unitId: {{ $unit['id'] }} })"
                />
            </div>
        </div>
    </div>
    {{-- /Node Card --}}

    {{-- ── Children ────────────────────────────────────────────────────────── --}}
    @if (!empty($unit['children']))
        {{-- Vertical connector: card → horizontal bar --}}
        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 shrink-0"></div>

        {{-- Horizontal children row --}}
        <div class="org-children-row flex items-start">
            @foreach ($unit['children'] as $child)
                <div class="org-child-slot flex flex-col items-center px-4">
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 shrink-0"></div>
                    @include('filament.pages.admin._unit-node', ['unit' => $child, 'isRoot' => false])
                </div>
            @endforeach

            {{-- "+ Tambah Unit" sibling slot --}}
            <div class="org-child-slot flex flex-col items-center px-4">
                <div class="w-px h-8 bg-transparent shrink-0"></div>
                <button
                    type="button"
                    wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
                    class="org-add-btn flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:border-primary-400 hover:text-primary-500 dark:hover:border-primary-600 dark:hover:text-primary-400 transition-colors w-56 py-6"
                >
                    <x-heroicon-o-plus class="h-5 w-5" />
                    <span class="text-xs font-medium">Tambah Unit</span>
                </button>
            </div>
        </div>

    @else
        {{-- Leaf: "Tambah Sub-Unit" below card --}}
        <button
            type="button"
            wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
            class="org-add-btn mt-2 flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 hover:border-primary-400 hover:text-primary-500 dark:hover:border-primary-600 dark:hover:text-primary-400 transition-colors"
        >
            <x-heroicon-o-plus class="h-3.5 w-3.5" />
            Tambah Sub-Unit
        </button>
    @endif
    {{-- /Children --}}

</div>
