<div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50"
    x-data="{
        isDragging: false,
        dragEl: null,
        startX: 0,
        startY: 0,
        initialLeft: 0,
        initialTop: 0,

        startDrag(e) {
            const el = e.target.closest('.draggable-signature');
            if (!el) return;

            e.preventDefault();
            this.isDragging = true;
            this.dragEl = el;
            this.startX = e.clientX;
            this.startY = e.clientY;

            const paper = this.$refs.paper;
            const style = window.getComputedStyle(el);

            if (style.position !== 'absolute') {
                const rect = el.getBoundingClientRect();
                const paperRect = paper.getBoundingClientRect();
                this.initialLeft = rect.left - paperRect.left;
                this.initialTop = rect.top - paperRect.top;

                el.style.position = 'absolute';
                el.style.left = this.initialLeft + 'px';
                el.style.top = this.initialTop + 'px';
            } else {
                this.initialLeft = parseInt(style.left, 10) || 0;
                this.initialTop = parseInt(style.top, 10) || 0;
            }

            el.style.cursor = 'grabbing';
            el.style.zIndex = 1000;
            el.style.border = '2px dashed var(--color-primary-500)';
        },

        onDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            const dx = e.clientX - this.startX;
            const dy = e.clientY - this.startY;

            this.dragEl.style.left = (this.initialLeft + dx) + 'px';
            this.dragEl.style.top = (this.initialTop + dy) + 'px';
        },

        stopDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            this.isDragging = false;
            this.dragEl.style.cursor = 'grab';
            this.dragEl.style.zIndex = '';
            this.dragEl.style.border = '';

            const key = this.dragEl.getAttribute('data-key');
            if (key) {
                const x = parseInt(this.dragEl.style.left, 10);
                const y = parseInt(this.dragEl.style.top, 10);

                // Filament V3 forms typically use the 'data' state path.
                // If 'data' exists, we use data.content.KEY
                let pathPrefix = 'data.content.';
                if ($wire.get('data') === undefined) {
                    pathPrefix = 'content.';
                }

                // Tell Filament's Livewire state to remember the new coordinates without triggering a network request
                $wire.set(pathPrefix + key + '_posisi_x', x, false);
                $wire.set(pathPrefix + key + '_posisi_y', y, false);
            }

            this.dragEl = null;
        }
    }"
    @mousedown="startDrag"
    @mousemove.window="onDrag"
    @mouseup.window="stopDrag"
>
    <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5">
        <div class="absolute top-0 right-0 rounded-bl-lg bg-primary-600 px-3 py-1 text-xs font-bold text-white shadow-sm z-10">
            PREVIEW
        </div>

        <div class="prose max-w-none prose-sm sm:prose-base dark:prose-invert relative">
            <!-- Ensure text remains readable inside the white paper container -->
            <style>
                .preview-paper-content { color: #000000; position: relative; min-height: 800px; }
                .preview-paper-content p, .preview-paper-content span, .preview-paper-content td, .preview-paper-content th { color: #000000 !important; }

                /* Drag hints for signatures */
                .draggable-signature {
                    user-select: none;
                    transition: outline 0.2s ease;
                    outline: 1px dotted transparent;
                }
                .draggable-signature:hover {
                    outline: 2px dashed #9ca3af;
                }
            </style>
            <div class="preview-paper-content" x-ref="paper">
                {!! $html !!}
            </div>
        </div>
    </div>
</div>
