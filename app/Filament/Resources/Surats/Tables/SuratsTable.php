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
use App\Models\KategoriArsip;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
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
                    ->visible(fn() => request('scope') != 'arsip'),

                TextColumn::make('arsip_kategori')
                    ->label('Diarsipkan Di')
                    ->badge()
                    ->visible(fn() => request('scope') === 'arsip')
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;

                        $arsip = $record->arsipSurats
                            ->firstWhere('unit_kerja_id', $unitId);

                        return $arsip?->kategoriArsip?->nama ?? '-';
                    })
                    ->color('success'),


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

                SelectFilter::make('kategori_arsip_id')
                    ->label('Kategori Arsip')
                    ->options(function () {
                        return KategoriArsip::query()
                            ->where('unit_kerja_id', Auth::user()->unit_kerja_id)
                            ->pluck('nama', 'id');
                    })
                    ->query(function ($query, $value) {
                        $unitId = Auth::user()->unit_kerja_id;

                        $query->whereHas(
                            'arsipSurats',
                            fn($q) =>
                            $q->where('unit_kerja_id', $unitId)
                                ->where('kategori_arsip_id', $value)
                        );
                    })
                    ->visible(fn() => request('scope') === 'arsip'),

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
            ->modifyQueryUsing(function ($query) {
                if (request('scope') === 'arsip') {
                    $query->with(['arsipSurats.kategoriArsip']);
                }
            })            

            ->toolbarActions([])
            ->emptyStateHeading('TIdak Ada Data Surat')
            ->emptyStateDescription('');
    }
}
