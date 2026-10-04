<?php

namespace App\Filament\Resources\MahasiswaSurats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SuratsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // ->poll('7s')
            ->columns([
                TextColumn::make('perihal')
                    ->label('Perihal')
                    ->searchable(),
                TextColumn::make('nomorSuratLogs.nomor_lengkap')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn(\App\Models\Surat $record) => $record->nomorSuratLogs->last()?->nomor_lengkap ?? '-'),
                TextColumn::make('status_surat')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('tracking_code')
                    ->label('Kode Lacak')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tgl Pengajuan')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                // 1. Aksi Lacak Langsung ke Halaman Tracking
                // \Filament\Actions\Action::make('lacak')
                //     ->label('Lacak')
                //     ->icon('heroicon-o-eye')
                //     ->url(fn(\App\Models\Surat $record) => url('/lacak?code=' . ($record->tracking_code ?: 'REQ-' . $record->id)))
                //     ->openUrlInNewTab(),

                \Filament\Actions\EditAction::make()
                    ->label('Perbaiki')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->visible(fn(\App\Models\Surat $record) => $record->status_surat === 'REVISI'),

                // 2. Aksi Lihat Modal Detail Internal
                \Filament\Actions\ViewAction::make(),
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
