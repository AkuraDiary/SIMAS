<div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-4 space-y-8 mt-4">
    @foreach ($this->timelineData as $event)
    <div class="relative mb-8 ml-8">
        <!-- Icon Badge -->
        <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-12 ring-4 ring-white dark:ring-gray-900 {{ $event['color'] }}">
            <x-filament::icon :icon="$event['icon']" class="w-4 h-4 text-white" />
        </span>

        <!-- Content -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $event['title'] }}</h3>
            <time class="text-sm font-normal text-gray-400 sm:order-last sm:mb-0">{{ \Carbon\Carbon::parse($event['date'])->format('d M Y, H:i') }}</time>
        </div>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
            Oleh: {{ $event['actor'] }}
            @if($event['unit'])
            <span class="text-gray-400">({{ $event['unit'] }})</span>
            @endif
        </p>

        <!-- Optional Catatan/Notes Box -->
        @if(!empty($event['catatan']))
        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <p class="text-sm font-normal text-gray-600 dark:text-gray-400">"{!! nl2br(e($event['catatan'])) !!}"</p>
        </div>
        @endif
    </div>
    @endforeach
</div>