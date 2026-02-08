<?php

namespace App\Filament\Resources\UnitKerjas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UnitKerjasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_unit')
                    ->searchable(),
                TextColumn::make('jenis_unit')
                    ->badge(),
                TextColumn::make('status_unit')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aktif' => 'success',
                        'nonaktif' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                SelectFilter::make('status_unit')->options([
                    'aktif' => 'Aktif',
                    'nonaktif' => 'Nonaktif',
                ])
                    ->label('Status Unit'),
                SelectFilter::make('jenis_unit')->options([
                    'fakultas' => 'Fakultas',
                    'non-fakultas' => 'Non Fakultas',
                ]),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Tidak Ada Data Unit Kerja')
            ->emptyStateDescription('');
    }
}
