<div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50"
    x-data="{
        isDragging: false,
        dragEl: null,
        startX: 0,
        startY: 0,
        initialLeft: 0,
        initialTop: 0,
        originPage: 1,

        isResizing: false,
        resizeEl: null,
        startWidth: 0,
        isDetailPage: @js((bool) ($isDetailPage ?? false)),

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

        savePosition(key, x, y, width = null, page = null) {
            if (!key) return;
            if (this.isDetailPage) {
                $wire.call('updateTtdPosition', key, x, y, width, page);
                return;
            }
            let pathPrefix = ($wire.get('data') === undefined) ? 'content.' : 'data.content.';
            if (x !== null && y !== null) {
                $wire.set(pathPrefix + key + '_posisi_x', x, false);
                $wire.set(pathPrefix + key + '_posisi_y', y, false);
            }
            if (width !== null) {
                $wire.set(pathPrefix + key + '_width', width, false);
            }
            if (page !== null) {
                $wire.set(pathPrefix + key + '_halaman', page, false);
            }
        },

        handleDblClick(e) {
            const el = e.target.closest('.draggable-signature');
            if (!el || el.getAttribute('data-locked') === 'true') return;
            el.style.left = '0px';
            el.style.top = '0px';
            const key = el.getAttribute('data-key');
            const sheet = el.closest('.surat-page-sheet');
            const page = sheet ? (parseInt(sheet.getAttribute('data-page'), 10) || 1) : null;
            this.savePosition(key, 0, 0, null, page);
        },

        startDrag(e) {
            const el = e.target.closest('.draggable-signature');
            if (!el || el.getAttribute('data-locked') === 'true') return;

            e.preventDefault();
            this.isDragging = true;
            this.dragEl = el;
            this.startX = e.clientX;
            this.startY = e.clientY;

            this.initialLeft = parseInt(el.style.left, 10) || 0;
            this.initialTop = parseInt(el.style.top, 10) || 0;

            const sheet = el.closest('.surat-page-sheet');
            this.originPage = sheet ? (parseInt(sheet.getAttribute('data-page'), 10) || 1) : 1;

            el.style.cursor = 'grabbing';
            el.style.zIndex = '50';
            el.classList.add('is-being-dragged');
        },

        onDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            const dx = e.clientX - this.startX;
            const dy = e.clientY - this.startY;

            // Izinkan gerak vertikal lintas beberapa lembar kertas (-2500px s/d +2500px)
            const newLeft = Math.max(-700, Math.min(700, this.initialLeft + dx));
            const newTop = Math.max(-2500, Math.min(2500, this.initialTop + dy));

            this.dragEl.style.left = newLeft + 'px';
            this.dragEl.style.top = newTop + 'px';

            // Highlight lembar kertas tujuan di bawah titik tengah TTD
            const rect = this.dragEl.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            document.querySelectorAll('.surat-page-sheet').forEach(sheet => {
                const sRect = sheet.getBoundingClientRect();
                if (centerY >= sRect.top && centerY <= sRect.bottom && centerX >= sRect.left && centerX <= sRect.right) {
                    sheet.classList.add('is-drop-target');
                } else {
                    sheet.classList.remove('is-drop-target');
                }
            });
        },

        stopDrag(e) {
            if (!this.isDragging || !this.dragEl) return;

            const el = this.dragEl;
            this.isDragging = false;
            el.style.cursor = 'grab';
            el.style.zIndex = '';
            el.classList.remove('is-being-dragged');

            document.querySelectorAll('.surat-page-sheet.is-drop-target').forEach(s => s.classList.remove('is-drop-target'));

            const key = el.getAttribute('data-key');
            if (key) {
                const dropRect = el.getBoundingClientRect();
                const centerX = dropRect.left + dropRect.width / 2;
                const centerY = dropRect.top + dropRect.height / 2;

                // Cari lembar kertas tempat TTD dijatuhkan
                let targetSheet = null;
                document.querySelectorAll('.surat-page-sheet').forEach(sheet => {
                    const sRect = sheet.getBoundingClientRect();
                    if (centerY >= sRect.top && centerY <= sRect.bottom) {
                        targetSheet = sheet;
                    }
                });

                const currentSheet = el.closest('.surat-page-sheet');
                const targetPage = targetSheet
                    ? (parseInt(targetSheet.getAttribute('data-page'), 10) || this.originPage)
                    : this.originPage;

                let x = parseInt(el.style.left, 10) || 0;
                let y = parseInt(el.style.top, 10) || 0;

                // Jika TTD dijatuhkan ke lembar halaman yang berbeda, pindahkan ke anchor halaman tujuan
                // dan hitung ulang offset (x, y) agar posisi visual tidak bergeser 1 piksel pun!
                if (targetSheet && currentSheet && targetSheet !== currentSheet) {
                    const targetAnchor = targetSheet.querySelector('.surat-page-ttd-anchor');
                    if (targetAnchor) {
                        el.style.left = '0px';
                        el.style.top = '0px';
                        targetAnchor.appendChild(el);

                        const anchorRect = el.getBoundingClientRect();
                        x = Math.round(dropRect.left - anchorRect.left);
                        y = Math.round(dropRect.top - anchorRect.top);

                        el.style.left = x + 'px';
                        el.style.top = y + 'px';
                        el.setAttribute('data-page', targetPage);
                    }
                }

                this.savePosition(key, x, y, null, targetPage);
            }
            this.dragEl = null;
        },

        startResize(e) {
            const handle = e.target.closest('.signature-resize-handle');
            if (!handle) return;
            const el = handle.closest('.draggable-signature');
            if (!el || el.getAttribute('data-locked') === 'true') return;

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
                const x = parseInt(el.style.left, 10) || 0;
                const y = parseInt(el.style.top, 10) || 0;
                const sheet = el.closest('.surat-page-sheet');
                const page = sheet ? (parseInt(sheet.getAttribute('data-page'), 10) || 1) : null;
                this.savePosition(key, x, y, width, page);
            }

            this.resizeEl = null;
        }
    }"
    @mousedown="handleMouseDown"
    @mousemove.window="handleMouseMove"
    @mouseup.window="handleMouseUp"
    @dblclick="handleDblClick">
    <div class="relative w-full max-w-3xl text-black">
        <div class="surat-content relative font-sans">
            <style>
                .preview-paper-content {
                    color: #000000;
                    position: relative;
                }

                /* Fallback jika HTML mentah tanpa .surat-page-sheet dirender */
                .preview-paper-content:not(:has(.surat-page-sheet)) {
                    background: #ffffff;
                    min-height: 800px;
                    padding: 2.5rem;
                    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                }

                .preview-paper-content p,
                .preview-paper-content span,
                .preview-paper-content td,
                .preview-paper-content th {
                    color: #000000 !important;
                }

                .draggable-signature[data-locked="true"] {
                    outline: none !important;
                    cursor: default !important;
                }

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
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
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
