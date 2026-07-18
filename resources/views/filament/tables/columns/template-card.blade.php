<div
    style="transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), filter 0.5s ease;"
    onmouseover="this.style.transform='scale(1.05)';"
    onmouseout="this.style.transform='scale(1)';">
    <!-- Top Half: Gray background with icon -->
    <div style="position: relative; height: 160px; background-color: #f1f5ff; display: flex; align-items: center; justify-content: center;">
        <!-- Status Badge -->
        <div style="position: absolute; top: 1rem; right: 1rem;">
            @if ($getRecord()->is_active)
            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; border-radius: 0.25rem; background-color: #d1fae5; color: #047857;">
                ACTIVE
            </span>
            @else
            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; border-radius: 0.25rem; background-color: #fef3c7; color: #b45309;">
                DRAFT
            </span>
            @endif
        </div>

        <!-- Document Icon -->
        <x-heroicon-o-document-text style="width: 3.5rem; height: 3.5rem; color: #212121;" />
    </div>

    <!-- Bottom Half: Content and Footer -->
    <div style="flex: 1 1 0%; display: flex; flex-direction: column;">
        <div style="padding: 1rem;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #1f2937; margin-bottom: 0.25rem; margin-top: 0;">
                {{ $getRecord()->nama_template }}

            </h3>
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; ">
                Last edit: {{ $getRecord()->updated_at->format('M d, Y') }}
            </span>
            <p style="margin-block: 0.5rem; font-size: 0.8rem; color: #6b7280; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: auto; height: 2.5rem;">
                {{ $getRecord()->deskripsi ?: 'No description provided.' }}
            </p>

        </div>


        <!-- Footer -->
        <div style="display: flex; align-items: end; justify-content: end;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button
                    type="button"
                    wire:click="mountTableAction('preview', '{{ $getRecord()->getKey() }}')"
                    style="display: inline-flex; align-items: center; padding: 0.35rem 0.75rem; background-color: #e0e7ff; color: #4338ca; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.2s;"
                    onmouseover="this.style.color='#4338ca'"
                    onmouseout="this.style.color='#4f46e5'">
                    <x-heroicon-o-eye style="width: 1.125rem; height: 1.125rem;  margin-inline: 0.5em;" />
                    Preview
                </button>

                <a href="{{ \App\Filament\Resources\TemplateResource\TemplateResource::getUrl('edit', ['record' => $getRecord()]) }}"
                    style="display: inline-flex; align-items: center; padding: 0.35rem 0.75rem; background-color: #e0e7ff; color: #4338ca; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#c7d2fe'"
                    onmouseout="this.style.backgroundColor='#e0e7ff'">
                    <x-heroicon-o-pencil style="width: 0.875rem; height: 0.875rem; margin-inline: 0.5em;" />
                    Edit
                </a>
            </div>
        </div>
    </div>
</div>