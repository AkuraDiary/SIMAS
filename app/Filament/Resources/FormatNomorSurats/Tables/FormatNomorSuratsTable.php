<?php

namespace App\Filament\Resources\FormatNomorSurats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FormatNomorSuratsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nama_format')
                    ->label('Nama Format')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('format_penomoran')
                    ->label('Template')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('nomor_urut_terakhir')
                    ->label('Nomor Terakhir')
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
