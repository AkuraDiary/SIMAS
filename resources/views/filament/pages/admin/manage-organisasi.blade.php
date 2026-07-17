
<x-filament-panels::page>

    {{-- ── Scoped Styles ─────────────────────────────────────────────────────── --}}
    <style>
        /* ── Canvas wrapper ── */
        #org-canvas-outer {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: calc(107vh - 14rem);
            min-height: 400px;
            background-color: hsl(220 14% 96%);
            border-radius: 0.75rem;
            border: 1px solid hsl(220 13% 91%);
            cursor: grab;
        }

        #org-canvas-outer.panning {
            cursor: grabbing;
        }

        .dark #org-canvas-outer {
            background-color: hsl(220 13% 10%);
            border-color: hsl(220 13% 18%);
        }

        /* ── Pannable/zoomable inner content ── */
        #org-canvas-inner {
            position: absolute;
            top: 0;
            left: 0;
            transform-origin: top left;
            padding: 5rem 4rem 8rem;
            will-change: transform;
            transition: transform 0.05s linear;
        }

        /* ── Children group container (dashed rounded box) ──
           Replaces the old horizontal-bracket connector approach.
           The border itself acts as the visual horizontal rail;
           vertical drops inside connect to each child card. */
        .org-children-group {
            /* border/radius/padding defined inline via Tailwind */
        }

        /* ── Card action button base ── */
        .org-card { transition: box-shadow 0.15s, border-color 0.15s; }
        .org-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }

        /* ── Toolbar glass card ── */
        .org-toolbar {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 10;
            display: flex;
            gap: 0.25rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid hsl(220 13% 91%);
            border-radius: 0.625rem;
            padding: 0.375rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .dark .org-toolbar {
            background: rgba(17, 24, 39, 0.85);
            border-color: hsl(220 13% 24%);
        }

        .org-toolbar button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            color: hsl(220 9% 40%);
            transition: background-color 0.15s, color 0.15s;
        }

        .org-toolbar button:hover {
            background: hsl(220 13% 91%);
            color: hsl(220 9% 20%);
        }

        .dark .org-toolbar button {
            color: hsl(220 9% 60%);
        }

        .dark .org-toolbar button:hover {
            background: hsl(220 13% 20%);
            color: hsl(220 9% 90%);
        }

        /* ── Legend glass card ── */
        .org-legend {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid hsl(220 13% 91%);
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            font-size: 0.75rem;
            min-width: 10rem;
        }

        .dark .org-legend {
            background: rgba(17, 24, 39, 0.85);
            border-color: hsl(220 13% 24%);
        }

        .org-legend-title {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: hsl(220 9% 50%);
            margin-bottom: 0.5rem;
        }

        .org-legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            height: 24;
            width: 24;
            color: hsl(220 9% 30%);
        }

        .dark .org-legend-item {
            color: hsl(220 9% 70%);
        }

        /* ── Scale badge ── */
        #zoom-label {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            z-index: 10;
            font-size: 0.7rem;
            color: hsl(220 9% 50%);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid hsl(220 13% 88%);
        }

        .dark #zoom-label {
            background: rgba(17, 24, 39, 0.7);
            border-color: hsl(220 13% 24%);
        }
    </style>

    {{-- ── Filament Action Modals ─────────────────────────────────────────────── --}}
    <x-filament-actions::modals />

    {{-- ── Page Header Actions ────────────────────────────── 
    <div class="flex justify-end gap-4 mb-3">
        <x-filament::button
            color="gray"
            outlined
            icon="heroicon-o-plus"
            wire:click="mountAction('createUnit')">
            Tambah Unit Utama
        </x-filament::button>
        {{ $this->addLevelAction }}
    </div>
    --}}

    {{-- ── Canvas ──────────────────────────────────────────────────────────────── --}}
    <div
        id="org-canvas-outer"
        x-data="orgCanvas()"
        x-on:mousedown="startPan($event)"
        x-on:mousemove="pan($event)"
        x-on:mouseup="endPan()"
        x-on:mouseleave="endPan()"
        x-on:wheel.passive="wheel($event)"
        x-bind:class="{ 'panning': isPanning }">
        {{-- Toolbar (zoom controls) --}}
        <div class="org-toolbar">
            <button type="button" title="Perbesar" x-on:click.stop="zoomIn()">
                <x-heroicon-o-magnifying-glass-plus class="h-4 w-4" />
            </button>
            <button type="button" title="Perkecil" x-on:click.stop="zoomOut()">
                <x-heroicon-o-magnifying-glass-minus class="h-4 w-4" />
            </button>
            <button type="button" title="Sesuaikan Layar" x-on:click.stop="fitToScreen()">
                <x-heroicon-o-arrows-pointing-in class="h-4 w-4" />
            </button>
        </div>

        {{-- Legend --}}
        <div class="org-legend">
            <p class="org-legend-title">Klasifikasi Unit</p>
            <div class="org-legend-item mb-1.5">
                <x-icon name="work" />
                <span>Administrasi</span>
            </div>
            <div class="org-legend-item mb-1.5">
                <x-icon name="school-o" />
                <span>Akademis</span>
            </div>
            <div class="org-legend-item mb-1.5">
                <x-icon name="o-building-office-2" />
                <span>Lainnya</span>
            </div>
        </div>

        {{-- Zoom level label --}}
        <div id="zoom-label" x-text="`${Math.round(scale * 100)}%`"></div>

        {{-- Zoomable + pannable content --}}
        <div
            id="org-canvas-inner"
            x-bind:style="`transform: translate(${translateX}px, ${translateY}px) scale(${scale});`">

            @if (!empty($treeData))
                {{-- Multiple root nodes rendered side-by-side --}}
                <div style="display:flex; gap:6rem; align-items:flex-start; justify-content:center;">
                    @foreach ($treeData as $root)
                        @include('filament.pages.admin._unit-node', [
                            'unit'   => $root,
                            'isRoot' => true,
                        ])
                    @endforeach
                </div>

            @else
                {{-- Empty state ── no units exist yet --}}
                <div style="display:flex; flex-direction:column; align-items:center; gap:1.25rem; padding:5rem 2rem;">

                    {{-- Illustration: work.svg asset --}}
                    <span style="display:inline-flex; width:3.5rem; height:3.5rem; overflow:hidden; color:#d1d5db;">
                        <x-icon name="work" style="width:100%; height:100%;" />
                    </span>

                    <p style="font-size:0.875rem; color:#9ca3af; margin:0;">Belum ada unit kerja.</p>

                    <button
                        type="button"
                        wire:click="mountAction('createUnit')"
                        style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.5rem 1.25rem; border-radius:0.5rem; border:none; background:var(--color-primary-600,#4f46e5); color:#ffffff; font-size:0.875rem; font-weight:500; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.15); transition:background 0.15s;"
                        onmouseover="this.style.background='var(--color-primary-500,#6366f1)';"
                        onmouseout="this.style.background='var(--color-primary-600,#4f46e5)';"
                    >
                        {{-- plus.svg asset --}}
                        <span style="display:inline-flex; width:1rem; height:1rem; overflow:hidden; flex-shrink:0;">
                            <x-icon name="plus" style="width:100%; height:100%;" />
                        </span>
                        Buat Unit Utama Pertama
                    </button>
                </div>
            @endif

        </div>
    </div>

    {{-- ── Alpine.js canvas controller ─────────────────────────────────────────── --}}
    <script>
        function orgCanvas() {
            return {
                scale: 1,
                translateX: 0,
                translateY: 0,
                isPanning: false,
                _startX: 0,
                _startY: 0,

                /** Zoom in by 10%, capped at 200% */
                zoomIn() {
                    this.scale = Math.min(2, parseFloat((this.scale + 0.1).toFixed(2)));
                },

                /** Zoom out by 10%, floored at 30% */
                zoomOut() {
                    this.scale = Math.max(0.3, parseFloat((this.scale - 0.1).toFixed(2)));
                },

                /** Reset pan & zoom */
                fitToScreen() {
                    this.scale = 1;
                    this.translateX = 0;
                    this.translateY = 0;
                },

                /** Mouse-wheel zoom (ctrl+scroll = zoom, plain scroll = pan) */
                wheel(event) {
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        const delta = event.deltaY > 0 ? -0.1 : 0.1;
                        this.scale = Math.min(2, Math.max(0.3, parseFloat((this.scale + delta).toFixed(2))));
                    } else {
                        this.translateX -= event.deltaX;
                        this.translateY -= event.deltaY;
                    }
                },

                /** Begin drag-pan */
                startPan(event) {
                    // Ignore clicks on interactive elements inside the canvas
                    if (event.target.closest('button, a, input, select')) return;
                    this.isPanning = true;
                    this._startX = event.clientX - this.translateX;
                    this._startY = event.clientY - this.translateY;
                },

                /** Track drag-pan movement */
                pan(event) {
                    if (!this.isPanning) return;
                    this.translateX = event.clientX - this._startX;
                    this.translateY = event.clientY - this._startY;
                },

                /** End drag-pan */
                endPan() {
                    this.isPanning = false;
                },
            };
        }
    </script>

</x-filament-panels::page>