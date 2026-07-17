{{--
    Recursive partial — single UnitKerja node.
    All visual styling uses inline styles (canvas context — Tailwind JIT purges classes here).
    SVG icons use existing assets from resources/svg/ via <x-icon name="...">.
    The assets have hardcoded width/height="24" which the blade-icons component respects,
    so we wrap them in a sized inline-flex span to enforce the display size we want.

    Variables: $unit (array), $isRoot (bool)
--}}
@php
$jenis = strtolower($unit['jenis_unit'] ?? '');
$isAkademis = str_contains($jenis, 'akademik')
|| str_contains($jenis, 'akademis')
|| str_contains($jenis, 'fakultas');
$isAdministrasi = str_contains($jenis, 'administrasi');
$isRootNode = $isRoot ?? false;

$cardBorder = $isRootNode
? 'border: 1.5px solid var(--color-primary-400, #818cf8); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary-400, #818cf8) 20%, transparent), 0 2px 8px rgba(0,0,0,0.08);'
: 'border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06);';

$cardOpacity = $unit['is_active'] ? '' : 'opacity: 0.5;';

$typeColor = $isAkademis
? 'color: #059669;'
: ($isAdministrasi ? 'color: #4f46e5;' : 'color: #9ca3af;');

// Icon name from resources/svg/
$typeIcon = $isAkademis ? 'school-o' : ($isAdministrasi ? 'work' : 'o-building-office-2');
@endphp

<div style="display:flex; flex-direction:column; align-items:center;">

    {{-- ── "TOP LEVEL UNIT" pill badge ── --}}
    @if ($isRootNode)
    <span style="
            display: inline-flex; align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.15rem 0.75rem;
            border-radius: 999px;
            background: var(--color-primary-600, #4f46e5);
            color: #fff;
            font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em;
            white-space: nowrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        ">Top Level Unit</span>
    @endif

    {{-- ── Node Card ── --}}
    <div style="
        position: relative; display: flex; flex-direction: column;
        width: 11rem; border-radius: 0.75rem;
        background: #ffffff; overflow: hidden;
        {{ $cardBorder }} {{ $cardOpacity }}
         transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), filter 0.5s ease;
    "
        onmouseover="this.style.transform='scale(1.05)'; this.style.filter='brightness(1.15)';"
        onmouseout="this.style.transform='scale(1)'; this.style.filter='brightness(1)';">
        <div style="padding: 0.75rem 0.875rem;">

            {{-- Row 1 — jenis label + type icon --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-bottom:0.5rem;">
                <span style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; {{ $typeColor }}">
                    {{ $unit['jenis_unit'] }}
                </span>
                {{-- Sized wrapper forces the 24×24 SVG asset to display at 1rem --}}
                <span style="display:inline-flex; width:1rem; height:1rem; flex-shrink:0; overflow:hidden; color:#9ca3af;">
                    <x-icon :name="$typeIcon" style="width:100%; height:100%;" />
                </span>
            </div>

            {{-- Row 2 — Unit name --}}
            <p style="font-size:0.875rem; font-weight:600; color:#111827; line-height:1.3; margin:0 0 0.2rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                {{ $unit['nama_unit'] }}
            </p>

            {{-- Row 3 — Abbreviation --}}
            <p style="font-size:0.7rem; color:#9ca3af; margin:0 0 0.625rem; line-height:1;">
                {{ $unit['singkatan'] }}
            </p>

            {{-- Divider --}}
            <div style="border-top:1px solid #f3f4f6; margin:0 -0.25rem 0.5rem;"></div>

            {{-- Footer — staff count | actions --}}
            <div style="display:flex; align-items:center; justify-content:space-between;">

                {{-- Staff count (employee-group-solid asset) --}}
                <div style="display:flex; align-items:center; gap:0.25rem; color:#9ca3af; font-size:0.75rem;">
                    <span style="display:inline-flex; width:0.875rem; height:0.875rem; flex-shrink:0; overflow:hidden;">
                        <x-icon name="employee-group-solid" style="width:100%; height:100%;" />
                    </span>
                    <span>{{ $unit['staff_count'] }}</span>
                </div>

                {{-- Filament icon-buttons (CSS from Filament bundle — not affected by JIT) --}}
                <div style="display:flex; align-items:center; margin-right:-0.5rem;">
                    <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="sm" tooltip="Hapus Unit" wire:click="mountAction('deleteUnit', { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button icon="heroicon-o-pencil-square" color="warning" size="sm" tooltip="Edit Unit" wire:click="mountAction('editUnit',   { unitId: {{ $unit['id'] }} })" />
                    <x-filament::icon-button icon="heroicon-o-user-group" color="primary" size="sm" tooltip="Lihat Staf" wire:click="mountAction('viewStaff',  { unitId: {{ $unit['id'] }} })" />
                </div>
            </div>
        </div>
    </div>
    {{-- /Node Card --}}

    {{-- ── Tree branches ── --}}
    @if (!empty($unit['children']))

    <div style="width:1px; height:1.25rem; background:#d1d5db; flex-shrink:0;"></div>

    {{-- Dashed children group container --}}
    <div style="display:flex; align-items:flex-start; border:1.5px dashed #d1d5db; border-radius:0.75rem; padding:0.75rem; gap:0;">

        @foreach ($unit['children'] as $child)
        <div style="display:flex; flex-direction:column; align-items:center; padding:0 0.75rem;">
            <div style="width:1px; height:1.25rem; background:#d1d5db; flex-shrink:0;"></div>
            @include('filament.pages.admin._unit-node', ['unit' => $child, 'isRoot' => false])
        </div>
        @endforeach

        {{-- "+ Tambah Unit" placeholder (plus.svg asset) --}}
        <div style="display:flex; flex-direction:column; align-items:center; padding:0 0.75rem;">
            <div style="width:1px; height:1.25rem; background:transparent; flex-shrink:0;"></div>
            <button
                type="button"
                wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
                style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.375rem; width:11rem; min-height:7.5rem; border-radius:0.75rem; border:2px dashed var(--color-primary-300,#a5b4fc); background:color-mix(in srgb, var(--color-primary-50,#eef2ff) 60%, transparent); color:var(--color-primary-400,#818cf8); font-size:0.75rem; font-weight:500; cursor:pointer; transition:border-color 0.15s,color 0.15s,background 0.15s;"
                onmouseover="this.style.borderColor='var(--color-primary-500,#6366f1)';this.style.color='var(--color-primary-600,#4f46e5)';"
                onmouseout="this.style.borderColor='var(--color-primary-300,#a5b4fc)';this.style.color='var(--color-primary-400,#818cf8)';">
                <span style="display:inline-flex; width:1rem; height:1rem; overflow:hidden;">
                    <x-icon name="plus" style="width:100%; height:100%;" />
                </span>
                Tambah Unit
            </button>
        </div>
    </div>

    @else

    {{-- Leaf: "+ Tambah Sub-Unit" (plus.svg asset) --}}
    <button
        type="button"
        wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
        style="display:flex; align-items:center; justify-content:center; gap:0.375rem; margin-top:0.375rem; width:11rem; padding:0.375rem 0; border-radius:0.5rem; border:1px dashed var(--color-primary-400,#818cf8); background:transparent; color:var(--color-primary-500,#6366f1); font-size:0.75rem; font-weight:500; cursor:pointer; transition:background 0.15s;"
        onmouseover="this.style.borderColor='var(--color-primary-500,#6366f1)';this.style.color='var(--color-primary-600,#4f46e5)';"
                onmouseout="this.style.borderColor='var(--color-primary-300,#a5b4fc)';this.style.color='var(--color-primary-400,#818cf8)';">
        <span style="display:inline-flex; width:0.75rem; height:0.75rem; overflow:hidden; flex-shrink:0;">
            <x-icon name="plus" style="width:100%; height:100%;" />
        </span>
        Buat Unit Dibawahnya
    </button>

    @endif

</div>