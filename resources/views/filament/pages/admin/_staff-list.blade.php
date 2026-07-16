{{-- Staff list shown inside the viewStaff modal --}}
<div class="space-y-2">
    @forelse ($staffList as $assignment)
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 font-semibold text-sm shrink-0">
                {{ mb_strtoupper(mb_substr($assignment->pegawai?->nama_lengkap ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ $assignment->pegawai?->nama_lengkap ?? '—' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ $assignment->pegawai?->nip ?? '—' }}
                    @if ($assignment->jabatan)
                        · {{ $assignment->jabatan->nama_jabatan }}
                    @endif
                </p>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-2 py-6 text-gray-400">
            <x-heroicon-o-user-group class="h-10 w-10" />
            <p class="text-sm">Belum ada staf aktif di unit ini.</p>
        </div>
    @endforelse
</div>
