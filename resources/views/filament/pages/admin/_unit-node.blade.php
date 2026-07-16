{{--
    Recursive partial — single UnitKerja node.
    All visual styling uses inline styles so they are guaranteed to render
    regardless of Tailwind JIT purging inside the canvas context.

    Variables: $unit (array), $isRoot (bool)
--}}
@php
    $jenis          = strtolower($unit['jenis_unit'] ?? '');
    $isAkademis     = str_contains($jenis, 'akademik')
                   || str_contains($jenis, 'akademis')
                   || str_contains($jenis, 'fakultas');
    $isAdministrasi = str_contains($jenis, 'administrasi');
    $isRootNode     = $isRoot ?? false;

    // ── Card border / shadow
    $cardBorder = $isRootNode
        ? 'border: 1.5px solid var(--color-primary-400, #818cf8); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary-400, #818cf8) 20%, transparent), 0 2px 8px rgba(0,0,0,0.08);'
        : 'border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06);';

    // ── Opacity for inactive units
    $cardOpacity = $unit['is_active'] ? '' : 'opacity: 0.5;';

    // ── Type-label color
    $typeColor = $isAkademis
        ? 'color: #059669;'          // emerald-600
        : ($isAdministrasi
            ? 'color: #4f46e5;'      // indigo-600
            : 'color: #9ca3af;');    // gray-400
@endphp

<div style="display:flex; flex-direction:column; align-items:center;">

    {{-- ── "TOP LEVEL UNIT" pill badge (floats above card) ── --}}
    @if ($isRootNode)
        <span style="
            display: inline-flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.15rem 0.75rem;
            border-radius: 999px;
            background: var(--color-primary-600, #4f46e5);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            white-space: nowrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        ">Top Level Unit</span>
    @endif

    {{-- ── Node Card ── --}}
    <div style="
        position: relative;
        display: flex;
        flex-direction: column;
        width: 11rem;
        border-radius: 0.75rem;
        background: #ffffff;
        overflow: hidden;
        {{ $cardBorder }}
        {{ $cardOpacity }}
    ">
        <div style="padding: 0.75rem 0.875rem;">

            {{-- Row 1 — jenis label + type icon --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-bottom:0.5rem;">
                <span style="
                    font-size: 9px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.1em;
                    line-height: 1;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    {{ $typeColor }}
                ">{{ $unit['jenis_unit'] }}</span>

                {{-- Type icon (inline SVG — not subject to JIT purging) --}}
                @if ($isAkademis)
                    <svg style="width:1rem;height:1rem;flex-shrink:0;color:#9ca3af;"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M0 0h24v24H0V0z" fill="none"/>
                        <path d="M12 3 1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                    </svg>
                @elseif ($isAdministrasi)
                    <svg style="width:1rem;height:1rem;flex-shrink:0;color:#9ca3af;"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 14C2 11.19 2 9.79 2.67 8.78A3.75 3.75 0 0 1 3.78 7.67C4.79 7 6.19 7 9 7h6c2.81 0 4.21 0 5.22.67a3.75 3.75 0 0 1 1.11 1.11C22 9.79 22 11.19 22 14c0 2.81 0 4.21-.68 5.22a3.75 3.75 0 0 1-1.1 1.11C19.21 21 17.81 21 15 21H9c-2.81 0-4.21 0-5.22-.67a3.75 3.75 0 0 1-1.11-1.11C2 18.21 2 16.81 2 14Z"
                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 7c0-1.89 0-2.83-.59-3.41C14.83 3 13.89 3 12 3c-1.89 0-2.83 0-3.41.59C8 4.17 8 5.11 8 7"
                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 11l.65.2C10.09 12.27 13.91 12.27 17.35 11.2L18 11M12 12v2"
                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @else
                    <svg style="width:1rem;height:1rem;flex-shrink:0;color:#9ca3af;"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                    </svg>
                @endif
            </div>

            {{-- Row 2 — Unit name --}}
            <p style="
                font-size: 0.875rem;
                font-weight: 600;
                color: #111827;
                line-height: 1.3;
                margin: 0 0 0.2rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            ">{{ $unit['nama_unit'] }}</p>

            {{-- Row 3 — Abbreviation --}}
            <p style="
                font-size: 0.7rem;
                color: #9ca3af;
                margin: 0 0 0.625rem;
                line-height: 1;
            ">{{ $unit['singkatan'] }}</p>

            {{-- Divider --}}
            <div style="border-top: 1px solid #f3f4f6; margin: 0 -0.25rem 0.5rem;"></div>

            {{-- Footer — staff count | actions --}}
            <div style="display:flex; align-items:center; justify-content:space-between;">

                {{-- Staff count --}}
                <div style="display:flex; align-items:center; gap:0.25rem; color:#9ca3af; font-size:0.75rem;">
                    <svg style="width:0.875rem;height:0.875rem;flex-shrink:0;"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                    <span>{{ $unit['staff_count'] }}</span>
                </div>

                {{-- Action icon-buttons (Filament component — CSS comes from Filament bundle) --}}
                <div style="display:flex; align-items:center; margin-right:-0.5rem;">
                    <x-filament::icon-button
                        icon="heroicon-o-trash"
                        color="danger"
                        size="sm"
                        tooltip="Hapus Unit"
                        wire:click="mountAction('deleteUnit', { unitId: {{ $unit['id'] }} })"
                    />
                    <x-filament::icon-button
                        icon="heroicon-o-pencil-square"
                        color="warning"
                        size="sm"
                        tooltip="Edit Unit"
                        wire:click="mountAction('editUnit', { unitId: {{ $unit['id'] }} })"
                    />
                    <x-filament::icon-button
                        icon="heroicon-o-user-group"
                        color="primary"
                        size="sm"
                        tooltip="Lihat Staf"
                        wire:click="mountAction('viewStaff', { unitId: {{ $unit['id'] }} })"
                    />
                </div>
            </div>
        </div>
    </div>
    {{-- /Node Card --}}

    {{-- ── Tree branches ── --}}
    @if (!empty($unit['children']))

        {{-- Vertical stem --}}
        <div style="width:1px; height:1.25rem; background:#d1d5db; flex-shrink:0;"></div>

        {{-- Dashed children group container --}}
        <div style="
            display: flex;
            align-items: flex-start;
            border: 1.5px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 0.75rem;
            gap: 0;
        ">
            @foreach ($unit['children'] as $child)
                <div style="display:flex; flex-direction:column; align-items:center; padding: 0 0.75rem;">
                    {{-- Vertical drop from container top to child --}}
                    <div style="width:1px; height:1.25rem; background:#d1d5db; flex-shrink:0;"></div>
                    @include('filament.pages.admin._unit-node', ['unit' => $child, 'isRoot' => false])
                </div>
            @endforeach

            {{-- "+ Tambah Unit" lavender placeholder --}}
            <div style="display:flex; flex-direction:column; align-items:center; padding: 0 0.75rem;">
                <div style="width:1px; height:1.25rem; background:transparent; flex-shrink:0;"></div>
                <button
                    type="button"
                    wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
                    style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        gap: 0.375rem;
                        width: 11rem;
                        min-height: 7.5rem;
                        border-radius: 0.75rem;
                        border: 2px dashed var(--color-primary-300, #a5b4fc);
                        background: color-mix(in srgb, var(--color-primary-50, #eef2ff) 60%, transparent);
                        color: var(--color-primary-400, #818cf8);
                        font-size: 0.75rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: border-color 0.15s, color 0.15s, background 0.15s;
                    "
                    onmouseover="this.style.borderColor='var(--color-primary-500,#6366f1)';this.style.color='var(--color-primary-600,#4f46e5)';"
                    onmouseout="this.style.borderColor='var(--color-primary-300,#a5b4fc)';this.style.color='var(--color-primary-400,#818cf8)';"
                >
                    <svg style="width:1rem;height:1rem;" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah Unit
                </button>
            </div>
        </div>

    @else

        {{-- Leaf: "+ Tambah Sub-Unit" --}}
        <button
            type="button"
            wire:click="mountAction('createUnit', { parentId: {{ $unit['id'] }} })"
            style="
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.375rem;
                margin-top: 0.375rem;
                width: 11rem;
                padding: 0.375rem 0;
                border-radius: 0.5rem;
                border: 1px dashed var(--color-primary-400, #818cf8);
                background: transparent;
                color: var(--color-primary-500, #6366f1);
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
                transition: background 0.15s;
            "
            onmouseover="this.style.background='color-mix(in srgb, var(--color-primary-50,#eef2ff) 70%, transparent)';"
            onmouseout="this.style.background='transparent';"
        >
            <svg style="width:0.75rem;height:0.75rem;flex-shrink:0;" xmlns="http://www.w3.org/2000/svg"
                 fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Sub-Unit
        </button>

    @endif

</div>