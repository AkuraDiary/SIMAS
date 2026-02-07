<?php

namespace App\Filament\Resources\UnitKerjas\RelationManagers;

use App\Filament\Resources\UnitKerjas\UnitKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Staf';

    protected static ?string $relatedResource = UnitKerjaResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->label('Password'),
                TextInput::make('nama_lengkap')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make('peran')
                    ->options(['stafunit' => 'Staf Unit'])
                    ->required(),
                Select::make('status_user')
                    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                    ->required(),
                Select::make('unit_kerja_id')
                    ->relationship('unitKerja', 'nama_unit')
                    ->searchable()
                    ->label("Unit Kerja")
                    ->preload(),

            ]);
    }



    public function table(Table $table): Table
    {
        return $table->headerActions(
            [
                CreateAction::make()->label("Tambahkan Staf")  ->modalHeading('Tambahkan Staf Unit'),
            ]
        )->columns([
            TextColumn::make('username')
                ->searchable(),
            TextColumn::make('nama_lengkap')
                ->searchable(),
            TextColumn::make('email')
                ->label('Email address')
                ->searchable(),
            TextColumn::make('peran')
                ->badge()
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'stafunit' => 'Staf Unit',
                    default => $state,
                }),
            TextColumn::make('status_user')
                ->badge()->color(fn(string $state): string => match ($state) {
                    'aktif' => 'success',
                    'nonaktif' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

        ])->recordActions([


            EditAction::make(),
            DeleteAction::make(),

        ]);
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function canEdit($record): bool
    {
        return true;
    }

    public function canDelete($record): bool
    {
        return true;
    }
}
