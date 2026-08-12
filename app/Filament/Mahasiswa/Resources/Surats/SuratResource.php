<?php

namespace App\Filament\Mahasiswa\Resources\Surats;

use App\Filament\Mahasiswa\Resources\Surats\Pages\CreateSurat;
use App\Filament\Mahasiswa\Resources\Surats\Pages\EditSurat;
use App\Filament\Mahasiswa\Resources\Surats\Pages\ListSurats;
use App\Filament\Mahasiswa\Resources\Surats\Schemas\SuratForm;
use App\Filament\Mahasiswa\Resources\Surats\Tables\SuratsTable;
use App\Models\Surat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_pembuat_id', auth()->id())
            ->where('tipe_surat', 'PENGAJUAN')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
