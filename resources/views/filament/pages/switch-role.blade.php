<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($jabatans as $jabatan)
            <x-filament::card class="{{ $activeJabatanId == $jabatan->id ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg">{{ $jabatan->jabatan->nama_jabatan }}</h3>
                        <p class="text-sm text-gray-500">{{ $jabatan->unitKerja->nama_unit }}</p>
                    </div>

                    @if($activeJabatanId == $jabatan->id)
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-xs font-bold">Sedang Aktif</span>
                    @else
                        <x-filament::button wire:click="switchRole({{ $jabatan->id }})" color="gray">
                            Gunakan Peran Ini
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::card>
        @endforeach
    </div>
</x-filament-panels::page>