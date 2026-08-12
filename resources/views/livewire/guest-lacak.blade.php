<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-indigo-600">
            <h3 class="text-lg leading-6 font-medium text-white">Lacak Pengajuan Surat</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <form wire:submit.prevent="search" class="flex items-center space-x-4">
                <div class="flex-1">
                    <label class="sr-only">Kode Pelacakan</label>
                    <input type="text" wire:model="trackingCode" placeholder="Masukkan Kode Pelacakan (Contoh: REQ-1-abc12345)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Cari
                </button>
            </form>
            @error('trackingCode') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            @if($errorMsg) <span class="text-red-500 text-sm mt-2 block font-bold">{{ $errorMsg }}</span> @endif
        </div>

        @if($searched && $surat)
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Perihal</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $surat->perihal }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Pengajuan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $surat->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status Saat Ini</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $surat->status_surat }}
                            </span>
                        </dd>
                    </div>
                    
                    @if($surat->status_surat === 'TERBIT')
                        @php
                            // Fetch the child Terbitan letter
                            $terbitan = \App\Models\Surat::where('tipe_surat', 'TERBITAN')
                                ->where('terbitan_for_surat_id', $surat->id)
                                ->first();
                        @endphp
                        
                        @if($terbitan)
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-green-200">
                                <dt class="text-sm font-medium text-green-600">Surat Balasan / Terbitan</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <div class="flex items-center justify-between bg-green-50 p-3 rounded-md border border-green-200">
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Surat Terbitan Tersedia</p>
                                            <p class="text-xs text-green-700">Tanggal: {{ $terbitan->created_at->format('d M Y') }}</p>
                                        </div>
                                        <a href="{{ route('surat.export', $terbitan->id) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                                            Download Surat
                                        </a>
                                    </div>
                                </dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>
        @endif
    </div>
</div>
