<?php

namespace App\Filament\Resources\Surats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Surat;
use App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat;
use App\Filament\Resources\Surats\Pages\EditSurat;
use Illuminate\Support\Facades\Request;

class SuratsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_agenda')
                    ->searchable(),
                TextColumn::make('nomor_surat')
                    ->searchable(),
                TextColumn::make('perihal')
                    ->searchable(),
                TextColumn::make('tanggal_buat')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('tanggal_kirim')
                    ->dateTime()
                    ->sortable(),


                TextColumn::make('status_surat')
                    ->badge()
                    ->visible(fn($record) => Request::query('scope') != 'arsip'),


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
            ])
            ->recordActions([
                EditAction::make()->visible(fn($record) => $record->status_surat === 'DRAFT'),
                DeleteAction::make()->visible(fn($record) => $record->status_surat === 'DRAFT'),

            ])
            ->recordUrl(
                fn(Surat $record) => $record->status_surat === 'DRAFT'
                    ? EditSurat::getUrl(['record' => $record->id])
                    : DetailSurat::getUrl(
                        parameters: [
                            'surat' => $record->id,
                            'scope' => request('scope') ?? 'masuk',
                        ],
                        panel: 'simas'
                    )
            )

            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading('TIdak Ada Data Surat')
            ->emptyStateDescription('');
    }
}
