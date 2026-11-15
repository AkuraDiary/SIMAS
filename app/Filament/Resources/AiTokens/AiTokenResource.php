<?php

namespace App\Filament\Resources\AiTokens;

use App\Filament\Resources\AiTokens\Pages\CreateAiToken;
use App\Filament\Resources\AiTokens\Pages\EditAiToken;
use App\Filament\Resources\AiTokens\Pages\ListAiTokens;
use App\Filament\Resources\AiTokens\Schemas\AiTokenForm;
use App\Filament\Resources\AiTokens\Tables\AiTokensTable;
use App\Models\AiToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AiTokenResource extends Resource
{
    protected static ?string $model = AiToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Konfigurasi AI';

    protected static ?string $pluralModelLabel = 'Konfigurasi AI';

    protected static ?string $modelLabel = 'Token AI';

    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas === 'ADMIN';
    }

    public static function form(Schema $schema): Schema
    {
        return AiTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiTokensTable::configure($table);
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
            'index' => ListAiTokens::route('/'),
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
