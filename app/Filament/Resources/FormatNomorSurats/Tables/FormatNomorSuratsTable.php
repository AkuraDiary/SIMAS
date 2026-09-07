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
                \Filament\Tables\Columns\TextColumn::make('unitKerja.nama_unit')
                    ->label('Cakupan Unit')
                    ->badge()
                    ->color(fn ($record) => $record->unit_kerja_id ? 'gray' : 'primary')
                    ->formatStateUsing(fn ($state) => $state ?? 'Global / Pusat')
                    ->visible(fn () => auth()->user()?->tipe_entitas === 'ADMIN')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('nama_format')
                    ->label('Nama Format')
                    ->searchable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'INTERNAL' => 'primary',
                        'PENGAJUAN' => 'info',
                        'TERBITAN' => 'success',
                        'EKSTERNAL' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'ALL' => 'Semua Tipe',
                        'INTERNAL' => 'Internal',
                        'PENGAJUAN' => 'Pengajuan',
                        'TERBITAN' => 'Terbitan',
                        'EKSTERNAL' => 'Eksternal',
                        default => $state ?? 'Semua',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('format_penomoran')
                    ->label('Pola Penomoran')
                    ->fontFamily('mono')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('padding_digit')
                    ->label('Digit')
                    ->alignCenter()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('nomor_urut_terakhir')
                    ->label('Nomor Terakhir')
                    ->alignCenter()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->options([
                        'ALL' => 'Semua Tipe',
                        'INTERNAL' => 'Internal',
                        'PENGAJUAN' => 'Pengajuan',
                        'TERBITAN' => 'Terbitan',
                        'EKSTERNAL' => 'Eksternal',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama_unit')
                    ->visible(fn () => auth()->user()?->tipe_entitas === 'ADMIN'),
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
