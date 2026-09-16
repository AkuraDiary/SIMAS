<?php

namespace App\Filament\Resources\Surats;

use App\Filament\Resources\Surats\Pages\CreateSurat;
use App\Filament\Resources\Surats\Pages\EditSurat;
use App\Filament\Resources\Surats\Pages\ListSurats;
use App\Filament\Resources\Surats\Schemas\SuratForm;
use App\Filament\Resources\Surats\Tables\SuratsTable;
use App\Models\Surat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

class SuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->tipe_entitas, ['ADMIN', 'STAF']);
    }



    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas === 'STAF';
    }
    public static function getNavigationItems(): array
    {
        $unitId = Auth::user()?->unit_kerja_id;
        $pendingCount = $unitId ? Surat::query()
            ->where('status_surat', 'DIPROSES')
            ->whereHas('riwayats', fn($q) => $q->where('status', 'MENUNGGU')->where('unit_tujuan_id', $unitId))
            ->count() : 0;

        return [

            NavigationItem::make('Surat Keluar')
                ->icon('heroicon-o-paper-airplane')
                ->url(static::getUrl('index', ['scope' => 'keluar']))
                ->isActiveWhen(fn() => Request::query('scope') === 'keluar'),

            NavigationItem::make('Draft Surat')
                ->icon('heroicon-o-pencil-square')
                ->url(static::getUrl('index', ['scope' => 'draft']))
                ->isActiveWhen(fn() => Request::query('scope') === 'draft'),

            NavigationItem::make('Arsip Surat')
                ->icon('heroicon-o-archive-box')
                ->url(static::getUrl('index', ['scope' => 'arsip']))
                ->isActiveWhen(fn() => Request::query('scope') === 'arsip'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }


    public static function form(Schema $schema): Schema
    {
        return SuratForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuratsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurats::route('/'),
            'create' => CreateSurat::route('/create'),
            'edit' => EditSurat::route('/{record}/edit'),
        ];
    }
}
