<x-layouts.app :showHeader="true" title="Verifikasi Tanda Tangan Digital - SIMAS">
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">

        <!-- KARTU UTAMA HASIL VERIFIKASI -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            @if($isValid)
                <!-- BANNER STATUS VALID (HIJAU / PRIMARY) -->
                <div class="bg-emerald-600 px-6 py-8 text-white text-center sm:text-left sm:flex sm:items-center sm:justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wider uppercase bg-white text-emerald-800 mb-1">
                                Dokumen Otentik
                            </span>
                            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">
                                Tanda Tangan Digital Terverifikasi
                            </h1>
                            <p class="text-emerald-100 text-xs sm:text-sm mt-0.5">
                                Dokumen ini resmi diterbitkan dan ditandatangani melalui Sistem Informasi Manajemen Arsip & Surat (SIMAS).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8 space-y-8">
                    <!-- INFORMASI PENANDATANGAN -->
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-primary-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Data Penandatangan Dokumen
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-200 rounded-xl p-5">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Nama Pejabat</p>
                                <p class="text-sm font-bold text-gray-900 mt-0.5">
                                    {{ $user->nama_lengkap ?? $user->name }}
                                </p>
                                @if($user->pegawai?->nip)
                                <p class="text-xs text-gray-500 font-mono mt-0.5">NIP. {{ $user->pegawai->nip }}</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 font-medium">Jabatan & Unit Kerja Saat TTD</p>
                                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                    {{ $ttd->jabatan_saat_ttd }}
                                </p>
                                <p class="text-xs text-gray-600 mt-0.5">
                                    {{ $ttd->unit_saat_ttd }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 font-medium">Waktu Penandatanganan</p>
                                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                    {{ \Carbon\Carbon::parse($ttd->signed_at)->translatedFormat('d F Y, H:i') }} WIB
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 font-medium">Jenis Tanda Tangan</p>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200 mt-0.5">
                                    {{ $ttd->tipe === 'UTAMA' ? 'Penandatangan Primer (Utama)' : 'Penandatangan Pendamping' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMASI DOKUMEN SURAT -->
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-primary-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Informasi Dokumen Surat
                        </h2>

                        <div class="border border-gray-200 rounded-xl divide-y divide-gray-100">
                            <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="text-xs text-gray-500 font-medium sm:w-1/3">Nomor Surat</span>
                                <span class="text-sm font-mono font-bold text-gray-900 sm:w-2/3">
                                    {{ $surat->nomor_surat ?? '(Belum Diberi Nomor)' }}
                                </span>
                            </div>

                            <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="text-xs text-gray-500 font-medium sm:w-1/3">Perihal / Judul</span>
                                <span class="text-sm font-bold text-gray-900 sm:w-2/3">
                                    {{ $surat->perihal }}
                                </span>
                            </div>

                            <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="text-xs text-gray-500 font-medium sm:w-1/3">Unit Penerbit</span>
                                <span class="text-sm text-gray-800 sm:w-2/3">
                                    {{ $surat->unitPengirim?->nama_unit ?? 'Unit Kerja Terkait' }}
                                </span>
                            </div>

                            <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="text-xs text-gray-500 font-medium sm:w-1/3">Status Dokumen</span>
                                <span class="sm:w-2/3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        {{ $surat->status_surat }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- UNDUH DOKUMEN RESMI (JIKA SUDAH FINAL) -->
                    @if($surat->hasMedia('dokumen-final'))
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 bg-primary-50/60 border border-primary-200 rounded-xl">
                        <div>
                            <h3 class="text-sm font-bold text-primary-950">Berkas Dokumen Resmi (PDF)</h3>
                            <p class="text-xs text-primary-700 mt-0.5">
                                Anda dapat mengunduh berkas salinan asli surat ini yang telah dibubuhi QR Code tanda tangan.
                            </p>
                        </div>
                        <a
                            href="{{ route('verify.ttd.download', $surat->id) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm shadow-sm transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh Dokumen Resmi
                        </a>
                    </div>
                    @endif
                </div>

            @else
                <!-- BANNER STATUS INVALID / TIDAK DITEMUKAN -->
                <div class="bg-red-600 px-6 py-8 text-white text-center sm:text-left sm:flex sm:items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 mx-auto sm:mx-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">
                            Tanda Tangan Tidak Ditemukan / Tidak Valid
                        </h1>
                        <p class="text-red-100 text-xs sm:text-sm mt-0.5">
                            Data tanda tangan digital atau surat yang dicari tidak terdaftar dalam pangkalan data resmi SIMAS.
                        </p>
                    </div>
                </div>

                <div class="p-8 text-center space-y-4">
                    <p class="text-sm text-gray-600 max-w-lg mx-auto">
                        Pastikan QR Code yang Anda pindai berasal dari dokumen resmi yang sah. Jika Anda menduga adanya pemalsuan dokumen, silakan hubungi bagian tata usaha unit kerja terkait.
                    </p>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold transition">
                        Kembali ke Beranda SIMAS
                    </a>
                </div>
            @endif

        </div>

        <p class="text-center text-xs text-gray-400 mt-8">
            &copy; {{ date('Y') }} SIMAS - Sistem Informasi Manajemen Arsip dan Surat. Seluruh hak cipta dilindungi.
        </p>

    </div>
</x-layouts.app>
