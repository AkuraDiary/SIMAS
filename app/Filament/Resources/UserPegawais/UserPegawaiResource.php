<?php

namespace App\Filament\Resources\UserPegawais;

use App\Filament\Resources\UserPegawais\Pages\CreateUserPegawai;
use App\Filament\Resources\UserPegawais\Pages\EditUserPegawai;
use App\Filament\Resources\UserPegawais\Pages\ListUserPegawais;
use App\Filament\Resources\UserPegawais\Pages\ViewUserPegawai;
use App\Filament\Resources\UserPegawais\Schemas\UserPegawaiForm;
use App\Filament\Resources\UserPegawais\Schemas\UserPegawaiInfolist;
use App\Filament\Resources\UserPegawais\Tables\UserPegawaisTable;
use App\Models\UserPegawai;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPegawaiResource extends Resource
{
    protected static ?string $model = UserPegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_lengkap';


    protected static ?string $label = 'Akun Pegawai';
    protected static ?string $title = 'Akun Pegawai';
    protected static ?string $modelLabel = 'Akun Pegawai';

    protected static ?string $navigationLabel = 'Akun Pegawai'; // for navigation

    // for breadcrumbs
    protected static ?string $pluralModelLabel = 'Akun Pegawai';


    public static function form(Schema $schema): Schema
    {
        return UserPegawaiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserPegawaiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserPegawaisTable::configure($table);
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
            'index' => ListUserPegawais::route('/'),
            'create' => CreateUserPegawai::route('/create'),
            'view' => ViewUserPegawai::route('/{record}'),
            'edit' => EditUserPegawai::route('/{record}/edit'),
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
