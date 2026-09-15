<div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-4 space-y-8 mt-4">
    @foreach ($this->timelineData as $event)
    <div class="relative mb-8 ml-8">
        <!-- Icon Badge -->
        <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-12 ring-4 ring-white dark:ring-gray-900 {{ $event['color'] }}">
            <x-filament::icon :icon="$event['icon']" class="w-4 h-4 text-white" />
        </span>

        <!-- Content -->
        <div class="flex flex-col gap-0.5 mb-2">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                {{ $event['title'] }}
            </h3>

            <time class="text-xs font-medium text-gray-500">
                {{ \Carbon\Carbon::parse($event['date'])->format('d M Y, H:i') }}
            </time>
        </div>

        @if(!empty($event['instruksi']))
        <p class="text-xs font-bold text-gray-600 dark:text-gray-400 my-2"> Instruksi : {{ $event['instruksi'] }}</p>
        @endif

        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
            Oleh: {{ $event['actor'] }}
            @if($event['unit'])
            <span class="text-gray-400">({{ $event['unit'] }})</span>
            @endif
        </p>

        {{-- Tampilan Tujuan Utama & Tembusan --}}
        @if(!empty($event['tujuan_utama']) || !empty($event['tembusan']))
        <div class="mt-3 space-y-2">
            @if(!empty($event['tujuan_utama']))
            <div class="text-xs">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Tujuan Utama:</span>
                <div class="flex flex-wrap gap-1.5 mt-1">
                    @foreach($event['tujuan_utama'] as $unit)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                        <span class="w-1.5 h-1.5 rounded-full {{ $unit['status_baca'] === 'SUDAH' ? 'bg-teal-500' : 'bg-gray-400' }}"></span>
                        {{ $unit['nama'] }}
                        @if($unit['status_baca'] === 'SUDAH')
                        <span class="text-[9px] text-teal-600 dark:text-teal-400">(Dibaca)</span>
                        @endif
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($event['tembusan']))
            <div class="text-xs">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Tembusan:</span>
                <div class="flex flex-wrap gap-1.5 mt-1">
                    @foreach($event['tembusan'] as $unit)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800">
                        <span class="w-1.5 h-1.5 rounded-full {{ $unit['status_baca'] === 'SUDAH' ? 'bg-teal-500' : 'bg-gray-400' }}"></span>
                        {{ $unit['nama'] }}
                        @if($unit['status_baca'] === 'SUDAH')
                        <span class="text-[9px] text-teal-600 dark:text-teal-400">(Dibaca)</span>
                        @endif
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Optional Catatan/Notes Box -->
        @if(!empty($event['catatan']))
        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">{!! nl2br(e($event['catatan'])) !!}</p>
        </div>
        @endif
    </div>
    @endforeach
</div>
