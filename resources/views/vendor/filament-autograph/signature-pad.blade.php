@php
use Filament\Support\Facades\FilamentView;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field">
    @php
    $isDisabled = $isDisabled();
    $isClearable = $isClearable();
    $isDownloadable = $isDownloadable();
    $downloadableFormats = $getDownloadableFormats();
    $downloadActionDropdownPlacement = $getDownloadActionDropdownPlacement() ?? 'bottom-start';
    $isUndoable = $isUndoable();
    $isConfirmable = $isConfirmable();
    $loadStrategy = $getLoadStrategy();

    $clearAction = $getAction('clear');
    $downloadAction = $getAction('download');
    $undoAction = $getAction('undo');
    $doneAction = $getAction('done');
    @endphp

    <div
        x-load="visible || event (ax-modal-opened)"
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-autograph-alpine', 'saade/filament-autograph') }}"
        x-data="signaturePadFormComponent({
            backgroundColor: @js($getBackgroundColor()),
            backgroundColorOnDark: @js($getBackgroundColorOnDark()),
            confirmable: @js($isConfirmable),
            disabled: @js($isDisabled),
            dotSize: {{ $getDotSize() }},
            exportBackgroundColor: @js($getExportBackgroundColor()),
            exportPenColor: @js($getExportPenColor()),
            filename: '{{ $getFilename() }}',
            maxWidth: {{ $getLineMaxWidth() }},
            minDistance: {{ $getMinDistance() }},
            minWidth: {{ $getLineMinWidth() }},
            penColor: @js($getPenColor()),
            penColorOnDark: @js($getPenColorOnDark()),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            throttle: {{ $getThrottle() }},
            velocityFilterWeight: {{ $getVelocityFilterWeight() }},
        })"
        >
        <canvas
            x-ref="canvas"
            wire:ignore
            @if ($isDisabled)
            class="w-full min-h-[450px] rounded-lg shadow-sm ring-1 ring-gray-950/10 bg-gray-50 opacity-75 dark:bg-transparent dark:ring-white/20"
            @else
            class="w-full min-h-[450px] rounded-lg shadow-sm ring-1 ring-gray-950/10 bg-white dark:bg-black dark:ring-white/20 transition duration-75"
            @endif></canvas>

        <div class="flex gap-3 items-center justify-end mt-4">
            @if ($isClearable)
            {{ $clearAction }}
            @endif

            @if ($isUndoable)
            {{ $undoAction }}
            @endif

            @if ($isDownloadable)
            <x-filament::dropdown placement="{{ $downloadActionDropdownPlacement }}">
                <x-slot name="trigger">
                    {{ $downloadAction }}
                </x-slot>

                <x-filament::dropdown.list>
                    @if (in_array(DownloadableFormat::PNG, $downloadableFormats))
                    <x-filament::dropdown.list.item
                        x-on:click="downloadAs('{{ DownloadableFormat::PNG->getMime() }}', '{{ DownloadableFormat::PNG->getExtension() }}')">
                        {{ DownloadableFormat::PNG->getLabel() }}
                    </x-filament::dropdown.list.item>
                    @endif

                    @if (in_array(DownloadableFormat::JPG, $downloadableFormats))
                    <x-filament::dropdown.list.item
                        x-on:click="downloadAs('{{ DownloadableFormat::JPG->getMime() }}', '{{ DownloadableFormat::JPG->getExtension() }}')">
                        {{ DownloadableFormat::JPG->getLabel() }}
                    </x-filament::dropdown.list.item>
                    @endif

                    @if (in_array(DownloadableFormat::SVG, $downloadableFormats))
                    <x-filament::dropdown.list.item
                        x-on:click="downloadAs('{{ DownloadableFormat::SVG->getMime() }}', '{{ DownloadableFormat::SVG->getExtension() }}')">
                        {{ DownloadableFormat::SVG->getLabel() }}
                    </x-filament::dropdown.list.item>
                    @endif
                </x-filament::dropdown.list>
            </x-filament::dropdown>
            @endif

            @if ($isConfirmable)
            {{ $doneAction }}
            @endif
        </div>
    </div>
</x-dynamic-component>