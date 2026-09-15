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
                        {{ \Carbon\Carbon::parse($event['date'])->format('d M Y, H:i') }} WIB
                    </time>
                </div>

                @if(!empty($event['instruksi']))
                <p class="text-xs font-bold text-gray-600 dark:text-gray-400 my-2">
                    Instruksi : {{ $event['instruksi'] }}
                </p>
                @endif

                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                    Oleh: {{ $event['actor'] }}
                    @if($event['unit'])
                    <span class="text-gray-400">({{ $event['unit'] }})</span>
                    @endif
                </p>

                <!-- DAFTAR TUJUAN UTAMA & TEMBUSAN (JIKA ADA) -->
                @if(!empty($event['tujuan_utama']) || !empty($event['tembusan']))
                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 space-y-2.5">
                    {{-- Tujuan Utama --}}
                    @if(!empty($event['tujuan_utama']))
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 block mb-1">
                            Tujuan Utama ({{ count($event['tujuan_utama']) }}):
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($event['tujuan_utama'] as $target)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                <x-filament::icon icon="heroicon-m-building-office" class="w-3.5 h-3.5 shrink-0" />
                                {{ is_array($target) ? $target['nama'] : $target }}
                                @if(is_array($target) && ($target['status_baca'] ?? '') === 'SUDAH')
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold" title="Sudah dibuka oleh unit">(Dibuka)</span>
                                @endif
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Tembusan --}}
                    @if(!empty($event['tembusan']))
                    <div class="{{ !empty($event['tujuan_utama']) ? 'pt-2 border-t border-gray-200 dark:border-gray-700' : '' }}">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 block mb-1">
                            Tembusan ({{ count($event['tembusan']) }}):
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($event['tembusan'] as $target)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                <x-filament::icon icon="heroicon-m-paper-airplane" class="w-3.5 h-3.5 shrink-0" />
                                {{ is_array($target) ? $target['nama'] : $target }}
                                @if(is_array($target) && ($target['status_baca'] ?? '') === 'SUDAH')
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold" title="Sudah dibuka oleh unit">(Dibuka)</span>
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
        <!-- Optional Catatan/Notes Box -->
        @if(!empty($event['catatan']))
        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">{!! nl2br(e($event['catatan'])) !!}</p>
        </div>
        @endif
    </div>
    @endforeach
</div>
