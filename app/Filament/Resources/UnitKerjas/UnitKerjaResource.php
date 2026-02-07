<?php

namespace App\Filament\Resources\UnitKerjas;

use App\Filament\Resources\UnitKerjas\Pages\CreateUnitKerja;
use App\Filament\Resources\UnitKerjas\Pages\EditUnitKerja;
use App\Filament\Resources\UnitKerjas\Pages\ListUnitKerjas;
use App\Filament\Resources\UnitKerjas\Pages\ViewUnitKerjas;
use App\Filament\Resources\UnitKerjas\Schemas\UnitKerjaForm;
use App\Filament\Resources\UnitKerjas\Tables\UnitKerjasTable;
use App\Models\UnitKerja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UnitKerjaResource extends Resource
{
    protected static ?string $model = UnitKerja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Unit Kerja';
    protected static ?string $label = 'Unit Kerja';
    protected static ?string $navigationLabel = 'Unit Kerja'; // for navigation
    protected static ?string $modelLabel = 'Unit Kerja';
    protected static ?string $pluralModelLabel = 'Unit Kerja'; // for breadcrumbs

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->peran === 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return UnitKerjaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitKerjasTable::configure($table);
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
            'index' => ListUnitKerjas::route('/'),
            'create' => CreateUnitKerja::route('/create'),
            'edit' => EditUnitKerja::route('/{record}/edit'),
            
        ];
    }
}
