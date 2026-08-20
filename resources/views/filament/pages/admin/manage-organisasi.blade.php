
<x-filament-panels::page>
    {{-- ── Filament Action Modals ─────────────────────────────────────────────── --}}
    <x-filament-actions::modals />

    {{-- ── Canvas ──────────────────────────────────────────────────────────────── --}}
    <div
        id="org-canvas-outer"
        x-data="orgCanvas()"
        x-on:mousedown="startPan($event)"
        x-on:mousemove="pan($event)"
        x-on:mouseup="endPan()"
        x-on:mouseleave="endPan()"
        x-on:wheel.passive="wheel($event)"
        class="relative overflow-hidden w-full h-[calc(107vh-14rem)] min-h-[400px] bg-gray-100 border border-gray-200 rounded-xl cursor-grab dark:bg-gray-900 dark:border-gray-800"
        x-bind:class="{ 'cursor-grabbing': isPanning }"
        >
        {{-- Toolbar (zoom controls) --}}
        <div class="absolute top-4 left-4 z-10 flex gap-1 p-1.5 bg-white/85 dark:bg-gray-900/85 backdrop-blur-md border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm">
            <button type="button" title="Perbesar" x-on:click.stop="zoomIn()" class="flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-magnifying-glass-plus class="h-4 w-4" />
            </button>
            <button type="button" title="Perkecil" x-on:click.stop="zoomOut()" class="flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-magnifying-glass-minus class="h-4 w-4" />
            </button>
            <button type="button" title="Sesuaikan Layar" x-on:click.stop="fitToScreen()" class="flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-arrows-pointing-in class="h-4 w-4" />
            </button>
        </div>

        {{-- Legend --}}
        <div class="absolute top-4 right-4 z-10 bg-white/85 dark:bg-gray-900/85 backdrop-blur-md border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm px-4 py-3 min-w-[10rem] text-xs">
            <p class="text-[0.65rem] font-bold tracking-wider uppercase text-gray-500 mb-2">Klasifikasi Unit</p>
            <div class="flex items-center gap-2 mb-1.5 text-gray-600 dark:text-gray-400">
                <x-icon name="work" class="w-4 h-4" />
                <span>Administrasi</span>
            </div>
            <div class="flex items-center gap-2 mb-1.5 text-gray-600 dark:text-gray-400">
                <x-icon name="school-o" class="w-4 h-4" />
                <span>Akademis</span>
            </div>
            <div class="flex items-center gap-2 mb-1.5 text-gray-600 dark:text-gray-400">
                <x-icon name="o-building-office-2" class="w-4 h-4" />
                <span>Lainnya</span>
            </div>
        </div>

        {{-- Zoom level label --}}
        <div class="absolute bottom-4 left-4 z-10 text-[0.7rem] text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm px-2 py-1 rounded-md border border-gray-200 dark:border-gray-800" x-text="`${Math.round(scale * 100)}%`"></div>

        {{-- Zoomable + pannable content --}}
        <div
            id="org-canvas-inner"
            class="absolute top-0 left-0 pt-20 pb-32 px-16 origin-top-left will-change-transform transition-transform duration-75 ease-linear"
            x-bind:style="`transform: translate(${translateX}px, ${translateY}px) scale(${scale});`">

            @if (!empty($treeData))
                {{-- Multiple root nodes rendered side-by-side --}}
                <div class="flex gap-24 items-start justify-center">
                    @foreach ($treeData as $root)
                        @include('filament.pages.admin._unit-node', [
                            'unit'   => $root,
                            'isRoot' => true,
                        ])
                    @endforeach
                </div>

            @else
                {{-- Empty state ── no units exist yet --}}
                <div class="flex flex-col items-center gap-5 py-20 px-8">

                    {{-- Illustration: work.svg asset --}}
                    <span class="inline-flex w-14 h-14 overflow-hidden text-gray-300 dark:text-gray-600">
                        <x-icon name="work" class="w-full h-full" />
                    </span>

                    <p class="text-sm text-gray-400 dark:text-gray-500 m-0">Belum ada unit kerja.</p>

                    <button
                        type="button"
                        wire:click="mountAction('createUnit')"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg border-none bg-primary-600 text-white text-sm font-medium cursor-pointer shadow-sm transition-colors hover:bg-primary-500 dark:hover:bg-primary-500"
                    >
                        {{-- plus.svg asset --}}
                        <span class="inline-flex w-4 h-4 overflow-hidden shrink-0">
                            <x-icon name="plus" class="w-full h-full" />
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

                zoomIn() {
                    this.scale = Math.min(2, parseFloat((this.scale + 0.1).toFixed(2)));
                },

                zoomOut() {
                    this.scale = Math.max(0.3, parseFloat((this.scale - 0.1).toFixed(2)));
                },

                fitToScreen() {
                    this.scale = 1;
                    this.translateX = 0;
                    this.translateY = 0;
                },

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

                startPan(event) {
                    if (event.target.closest('button, a, input, select')) return;
                    this.isPanning = true;
                    this._startX = event.clientX - this.translateX;
                    this._startY = event.clientY - this.translateY;
                },

                pan(event) {
                    if (!this.isPanning) return;
                    this.translateX = event.clientX - this._startX;
                    this.translateY = event.clientY - this._startY;
                },

                endPan() {
                    this.isPanning = false;
                },
            };
        }
    </script>
</x-filament-panels::page>
