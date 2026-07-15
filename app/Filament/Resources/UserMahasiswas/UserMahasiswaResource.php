<?php

namespace App\Filament\Resources\UserMahasiswas;

use App\Filament\Resources\UserMahasiswas\Pages\CreateUserMahasiswa;
use App\Filament\Resources\UserMahasiswas\Pages\EditUserMahasiswa;
use App\Filament\Resources\UserMahasiswas\Pages\ListUserMahasiswas;
use App\Filament\Resources\UserMahasiswas\Pages\ViewUserMahasiswa;
use App\Filament\Resources\UserMahasiswas\Schemas\UserMahasiswaForm;
use App\Filament\Resources\UserMahasiswas\Schemas\UserMahasiswaInfolist;
use App\Filament\Resources\UserMahasiswas\Tables\UserMahasiswasTable;
use App\Models\UserMahasiswa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserMahasiswaResource extends Resource
{
    protected static ?string $model = UserMahasiswa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama_lengkap';


    protected static ?string $label = 'Akun Mahasiswa';
    protected static ?string $title = 'Akun Mahasiswa';
    protected static ?string $modelLabel = 'Akun Mahasiswa';

    protected static ?string $navigationLabel = 'Akun Mahasiswa'; // for navigation

    // for breadcrumbs
    protected static ?string $pluralModelLabel = 'Akun Mahasiswa';


    public static function form(Schema $schema): Schema
    {
        return UserMahasiswaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserMahasiswaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserMahasiswasTable::configure($table);
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
            'index' => ListUserMahasiswas::route('/'),
            'create' => CreateUserMahasiswa::route('/create'),
            'view' => ViewUserMahasiswa::route('/{record}'),
            'edit' => EditUserMahasiswa::route('/{record}/edit'),
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
