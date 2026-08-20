<!-- resources/views/components/image-modal.blade.php -->
<div
    x-data="{ isOpen: false, imageUrl: '' }"
    @open-image-modal.window="imageUrl = $event.detail.url; isOpen = true"
    x-show="isOpen"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm"
    @keydown.escape.window="isOpen = false"
>
    <!-- Click outside to close -->
    <div class="absolute inset-0" @click="isOpen = false"></div>

    <!-- Modal Container -->
    <div
        x-show="isOpen"
        x-transition.opacity.duration.300ms
        class="relative z-10 max-w-4xl w-full max-h-[90vh] flex flex-col bg-transparent rounded-xl overflow-hidden shadow-2xl"
    >
        <!-- Close Button -->
        <button
            @click="isOpen = false"
            class="absolute top-4 right-4 p-2 bg-gray-900/50 hover:bg-gray-900 text-white rounded-full transition-colors z-20"
        >
            <x-heroicon-o-x-mark class="w-6 h-6" />
        </button>

        <!-- The Enflated Image -->
        <img :src="imageUrl" class="object-contain w-full h-full max-h-[90vh] mx-auto rounded-lg shadow-lg" alt="Preview Bukti Besar" />
    </div>
</div>