<?php

namespace App\Filament\Resources\TemplateResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Template;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateStatsWidget extends BaseWidget
{
    // Fix: Make sure it strictly matches int or array|int|null
    protected int | array | null $columns = 2;

    // Optional: Forces the widget element itself to span full container width
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeCount = Template::where('is_active', true)->count();
        $draftCount = Template::where('is_active', false)->count();

        return [
            Stat::make('Active Templates', $activeCount)
                ->description('Templates available for use')
                ->descriptionIcon('heroicon-m-check-badge', \Filament\Support\Enums\IconPosition::Before)
                ->color('success'),
            Stat::make('Drafting', $draftCount)
                ->description('Templates in progress')
                ->descriptionIcon('heroicon-m-pencil-square', \Filament\Support\Enums\IconPosition::Before)
                ->color('warning'),
        ];
    }
}
