<?php

namespace App\Filament\Resources\UnitKerjas\RelationManagers;

use App\Filament\Resources\UnitKerjas\UnitKerjaResource;
use App\Filament\Resources\Users\Schemas\UserForm;
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
        // return UserForm::configure($schema);
        $schema = UserForm::configure($schema);

        // Find the unit_kerja_id field and modify it
        $components = $schema->getComponents();

        foreach ($components as $component) {
            // We use the getName() method to find your specific field
            if (method_exists($component, 'getName') && $component->getName() === 'unit_kerja_id') {
                $component
                    ->default($this->getOwnerRecord()->id)
                    ->disabled() // Optional: prevents user from changing it
                    ->hidden();   // Hide it since it's already contextual
            }
        }

        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()
                    ->label("Tambahkan Staf")
                    ->modalHeading('Tambahkan Staf Unit'),
            ])
            ->columns([
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
