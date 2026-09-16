<div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50"
    x-data="{
        isDragging: false,
        dragEl: null,
        startX: 0,
        startY: 0,
        initialLeft: 0,
        initialTop: 0,

        isResizing: false,
        resizeEl: null,
        startWidth: 0,

        handleMouseDown(e) {
            if (e.target.closest('.signature-resize-handle')) {
                this.startResize(e);
            } else if (e.target.closest('.draggable-signature')) {
                this.startDrag(e);
            }
        },

        handleMouseMove(e) {
            if (this.isResizing) {
                this.onResize(e);
            } else if (this.isDragging) {
                this.onDrag(e);
            }
        },

        handleMouseUp(e) {
            if (this.isResizing) {
                this.stopResize(e);
            }
            if (this.isDragging) {
                this.stopDrag(e);
            }
        },

        handleDblClick(e) {
            const el = e.target.closest('.draggable-signature');
            if (!el) return;
            // Reset position on double click
            el.style.left = '0px';
            el.style.top = '0px';
            const key = el.getAttribute('data-key');
            if (key) {
                let pathPrefix = ($wire.get('data') === undefined) ? 'content.' : 'data.content.';
                $wire.set(pathPrefix + key + '_posisi_x', 0, false);
                $wire.set(pathPrefix + key + '_posisi_y', 0, false);
            }
        },

        startDrag(e) {
            const el = e.target.closest('.draggable-signature');
            if (!el) return;

            e.preventDefault();
            this.isDragging = true;
            this.dragEl = el;
            this.startX = e.clientX;
            this.startY = e.clientY;

            // el has position: relative; left and top represent the relative displacement
            this.initialLeft = parseInt(el.style.left, 10) || 0;
            this.initialTop = parseInt(el.style.top, 10) || 0;

            el.style.cursor = 'grabbing';
            el.style.zIndex = '50';
            el.classList.add('is-being-dragged');
        },

        onDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            const dx = e.clientX - this.startX;
            const dy = e.clientY - this.startY;

            // Clamping offset within reasonable bounds (-300px to +300px)
            const newLeft = Math.max(-300, Math.min(300, this.initialLeft + dx));
            const newTop = Math.max(-300, Math.min(300, this.initialTop + dy));

            this.dragEl.style.left = newLeft + 'px';
            this.dragEl.style.top = newTop + 'px';
        },

        stopDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            const el = this.dragEl;
            this.isDragging = false;
            el.style.cursor = 'grab';
            el.style.zIndex = '';
            el.classList.remove('is-being-dragged');

            const key = el.getAttribute('data-key');
            if (key) {
                const x = parseInt(el.style.left, 10) || 0;
                const y = parseInt(el.style.top, 10) || 0;

                let pathPrefix = ($wire.get('data') === undefined) ? 'content.' : 'data.content.';
                $wire.set(pathPrefix + key + '_posisi_x', x, false);
                $wire.set(pathPrefix + key + '_posisi_y', y, false);
            }

            this.dragEl = null;
        },

        startResize(e) {
            const handle = e.target.closest('.signature-resize-handle');
            if (!handle) return;
            const el = handle.closest('.draggable-signature');
            if (!el) return;

            e.preventDefault();
            e.stopPropagation();

            this.isResizing = true;
            this.resizeEl = el;
            this.startX = e.clientX;
            this.startWidth = el.offsetWidth;

            el.classList.add('is-being-resized');
        },

        onResize(e) {
            if (!this.isResizing || !this.resizeEl) return;

            const dx = e.clientX - this.startX;
            const newWidth = Math.max(60, Math.min(450, this.startWidth + dx));
            this.resizeEl.style.width = newWidth + 'px';

            const img = this.resizeEl.querySelector('img');
            if (img) {
                img.style.width = '100%';
                img.style.maxWidth = newWidth + 'px';
                img.style.height = 'auto';
            }
        },

        stopResize(e) {
            if (!this.isResizing || !this.resizeEl) return;

            const el = this.resizeEl;
            this.isResizing = false;
            el.classList.remove('is-being-resized');

            const key = el.getAttribute('data-key');
            if (key) {
                const width = parseInt(el.style.width, 10);
                let pathPrefix = ($wire.get('data') === undefined) ? 'content.' : 'data.content.';
                $wire.set(pathPrefix + key + '_width', width, false);
            }

            this.resizeEl = null;
        }
    }"
    @mousedown="handleMouseDown"
    @mousemove.window="handleMouseMove"
    @mouseup.window="handleMouseUp"
    @dblclick="handleDblClick"
>
    <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5">
        <div class="absolute top-0 right-0 rounded-bl-lg bg-primary-600 px-3 py-1 text-xs font-bold text-white shadow-sm z-10">
            PREVIEW
        </div>

        <div class="surat-content relative font-sans">
            <!-- Ensure text remains readable inside the white paper container -->
            <style>
                .preview-paper-content { color: #000000; position: relative; min-height: 800px; }
                .preview-paper-content p, .preview-paper-content span, .preview-paper-content td, .preview-paper-content th { color: #000000 !important; }

                /* Drag & Resize hints for signatures */
                .draggable-signature {
                    user-select: none;
                    cursor: grab;
                    position: relative;
                    display: inline-block;
                    outline: 1.5px dashed rgba(59, 130, 246, 0.4);
                    border-radius: 4px;
                    transition: outline 0.15s ease;
                }
                .draggable-signature:hover {
                    outline: 2px dashed #2563eb;
                }
                .draggable-signature.is-being-dragged {
                    outline: 2px solid #2563eb;
                    cursor: grabbing;
                }
                .draggable-signature.is-being-resized {
                    outline: 2px dashed #3b82f6;
                }

                .signature-resize-handle {
                    position: absolute;
                    right: -6px;
                    bottom: -6px;
                    width: 14px;
                    height: 14px;
                    background: #2563eb;
                    border: 2px solid #ffffff;
                    border-radius: 50%;
                    cursor: se-resize;
                    z-index: 20;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                    transition: transform 0.15s ease, background-color 0.15s ease;
                }
                .signature-resize-handle:hover {
                    transform: scale(1.3);
                    background: #1d4ed8;
                }
            </style>
            <div class="preview-paper-content" x-ref="paper">
                {!! $html !!}
            </div>
        </div>
    </div>
</div>
