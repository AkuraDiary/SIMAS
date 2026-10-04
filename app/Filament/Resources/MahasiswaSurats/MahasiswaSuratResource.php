<?php

namespace App\Filament\Resources\MahasiswaSurats;

use App\Filament\Resources\MahasiswaSurats\Pages\CreateMahasiswaSurat;
use App\Filament\Resources\MahasiswaSurats\Pages\EditMahasiswaSurat;
use App\Filament\Resources\MahasiswaSurats\Pages\ListMahasiswaSurats;
use App\Filament\Resources\MahasiswaSurats\Schemas\SuratForm;
use App\Filament\Resources\MahasiswaSurats\Tables\SuratsTable;
use App\Models\Surat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MahasiswaSurats\Pages\ViewMahasiswaSurat;

class MahasiswaSuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static ?string $slug = 'mahasiswa-surats';

    protected static ?string $navigationLabel = 'Pengajuan';

    protected static ?string $modelLabel = 'Pengajuan Surat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    public static function canViewAny(): bool
    {
        return auth()->user()->tipe_entitas === 'MAHASISWA';
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
            'index' => ListMahasiswaSurats::route('/'),
            'create' => CreateMahasiswaSurat::route('/create'),
            'view'   => ViewMahasiswaSurat::route('/{record}'),

            'edit'   => EditMahasiswaSurat::route('/{record}/edit'),
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
