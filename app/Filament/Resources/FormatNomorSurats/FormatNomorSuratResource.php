<?php

namespace App\Filament\Resources\FormatNomorSurats;

use App\Filament\Resources\FormatNomorSurats\Pages\CreateFormatNomorSurat;
use App\Filament\Resources\FormatNomorSurats\Pages\EditFormatNomorSurat;
use App\Filament\Resources\FormatNomorSurats\Pages\ListFormatNomorSurats;
use App\Filament\Resources\FormatNomorSurats\Schemas\FormatNomorSuratForm;
use App\Filament\Resources\FormatNomorSurats\Tables\FormatNomorSuratsTable;
use App\Models\FormatNomorSurat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FormatNomorSuratResource extends Resource
{
    protected static ?string $model = FormatNomorSurat::class;


    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->tipe_entitas, ['ADMIN', 'STAF']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Staf hanya boleh melihat format milik unitnya sendiri
        if (Auth::user()?->tipe_entitas === 'STAF') {
            $query->where('unit_kerja_id', Auth::user()->unit_kerja_id);
        }

        return $query;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    // protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Format Nomor Surat';
    protected static ?string $pluralLabel = 'Format Nomor Surat';
    protected static ?string $modelLabel = 'Format Nomor Surat';

    public static function form(Schema $schema): Schema
    {
        return FormatNomorSuratForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormatNomorSuratsTable::configure($table);
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
            'index' => ListFormatNomorSurats::route('/'),
            'create' => CreateFormatNomorSurat::route('/create'),
            'edit' => EditFormatNomorSurat::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
