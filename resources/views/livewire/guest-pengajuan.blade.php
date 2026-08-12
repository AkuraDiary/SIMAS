<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Form Pengajuan Surat (Guest)</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Silakan lengkapi form di bawah ini untuk mengajukan permohonan surat.</p>
        </div>

        @if($submitted)
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6 bg-green-50 text-center">
                <h4 class="text-lg font-bold text-green-800 mb-2">Pengajuan Berhasil!</h4>
                <p class="text-green-700 mb-4">Harap simpan kode pelacakan berikut untuk mengecek status permohonan Anda:</p>
                <div class="inline-block bg-white border border-green-300 rounded px-4 py-3 text-2xl font-mono text-green-900">
                    {{ $trackingCode }}
                </div>
                <div class="mt-6">
                    <a href="{{ route('lacak') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                        Lacak Surat Sekarang
                    </a>
                </div>
            </div>
        @else
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <form wire:submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" wire:model="pengirim_nama" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('pengirim_nama') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="pengirim_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('pengirim_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Perihal / Tujuan Permohonan</label>
                        <input type="text" wire:model="perihal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('perihal') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan Tambahan</label>
                        <textarea wire:model="content" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @error('content') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lampiran (Bisa lebih dari 1)</label>
                        <input type="file" wire:model="lampiran" multiple class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('lampiran.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
