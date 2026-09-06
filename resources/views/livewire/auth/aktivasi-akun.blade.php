<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-[45%] w-full bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- Header Banner -->
        <div class="bg-linear-to-r from-primary-500 to-primary-600 px-6 py-8 text-center text-white">
            <h2 class="text-2xl font-bold tracking-tight">SIMAS</h2>
            <p class="text-white text-xs mt-1">Portal Layanan & Aktivasi Akun Pengguna</p>
        </div>

        <div class="p-8">
            <!-- STATE 1: LOADING -->
             @if ($status === 'loading')
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-primary-600 border-t-transparent"></div>
                    <p class="mt-4 text-sm text-gray-600 font-medium">Memverifikasi tautan aktivasi...</p>
                </div>

            <!-- STATE 2: CONFIRMATION FORM -->
             @elseif ($status === 'confirm')
                <div class="mb-6 text-center">
                    <div class="mx-auto w-12 h-12 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Konfirmasi Aktivasi Akun</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Halo, <strong class="text-gray-900">{{ $namaUser }}</strong>. Silakan masukkan NIP / NIM Anda untuk mengonfirmasi identitas pemilik akun.
                    </p>
                </div>

                <form wire:submit.prevent="konfirmasiAktivasi" class="space-y-4">
                    <div>
                        <label for="input_identifier" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            NIP / NIM <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="input_identifier" wire:model.defer="input_identifier"
                            class="outline-none border-gray-300  w-full px-4 py-2.5 rounded-lg border @error('input_identifier') border-red-500 @else @enderror focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                            placeholder="Masukkan NIP atau NIM Anda" autofocus>
                         @error('input_identifier')
                            <p class="mt-1 text-xs text-red-600"> bebek  $message </p>
                         @enderror
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Kata Sandi Baru <span class="text-gray-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <input type="password" id="new_password" wire:model.defer="new_password"
                            class="outline-none border-gray-300  w-full px-4 py-2.5 rounded-lg border @error('new_password') border-red-500 @else @enderror focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                            placeholder="Biarkan kosong jika tetap menggunakan NIP/NIM">
                         @error('new_password')
                            <p class="mt-1 text-xs text-red-600"> bebek goyeng  $message </p>
                         @enderror
                    </div>

                     @if(filled($new_password))
                        <div>
                            <label for="new_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <input type="password" id="new_password_confirmation" wire:model.defer="new_password_confirmation"
                                class="outline-none w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                placeholder="Ketik ulang kata sandi baru">
                        </div>
                     @endif

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-3 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold text-sm rounded-lg shadow transition flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="konfirmasiAktivasi">Aktifkan Akun Saya</span>
                            <span wire:loading wire:target="konfirmasiAktivasi">Memproses...</span>
                        </button>
                    </div>
                </form>

            <!-- STATE 3: SUCCESS -->
             @elseif ($status === 'success')
                <div class="text-center py-4">
                    <div class="mx-auto w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Aktivasi Akun Berhasil!</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        Akun Anda telah aktif dan siap digunakan. Anda dapat langsung masuk ke portal internal menggunakan kredensial Anda.
                    </p>
                    <div class="mt-6">
                        <a href="{{ url('/internal/login') }}"
                            class="inline-block w-full py-3 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold text-sm rounded-lg shadow transition">
                            Masuk ke Aplikasi (Login)
                        </a>
                    </div>
                </div>

            <!-- STATE 4: ERROR / EXPIRED -->
                  @elseif ($status === 'error')
                <div class="text-center py-4">
                    <div class="mx-auto w-14 h-14 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Tautan Tidak Dapat Digunakan</h3>
                    <p class="text-sm text-gray-600 mt-2">{{ $errorMessage }}</p>

                    <div class="mt-6 space-y-2">
                        @if ($alreadyActive)
                            <a href="{{ url('/internal/login') }}"
                                class="inline-block w-full py-2.5 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold text-sm rounded-lg shadow transition">
                                Menuju Halaman Login
                            </a>
                         @else
                            <a href="{{ url('/') }}"
                                class="inline-block w-full py-2.5 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold text-sm rounded-lg transition">
                                Kembali ke Beranda
                            </a>
                         @endif
                    </div>
                </div>
             @endif

        </div>
    </div>
</div>
