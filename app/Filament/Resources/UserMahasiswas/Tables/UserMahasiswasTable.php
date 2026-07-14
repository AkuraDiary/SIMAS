<?php

namespace App\Filament\Resources\UserMahasiswas\Tables;

use App\Filament\Imports\UserMahasiswaImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UserMahasiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->searchPlaceholder('Cari nama, email, atau NIM...')
        
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn() => null)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->nama_lengkap) . '&background=random'),
            
                TextColumn::make('nama_lengkap')
                    ->label('Nama Pengguna')
                    ->searchable(),
                TextColumn::make('nim')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('prodi.nama_unit')
                    ->label('Prodi')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'AKTIF' => 'success',
                        'CUTI' => 'warning',
                        'LULUS' => 'info',
                        'KELUAR' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('user.is_active')
                    ->label('Akun Aktif')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Aktif' : 'Belum Aktivasi')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('tanggal_lahir')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tahun_masuk')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fakultas.nama_unit')
                    ->label('Fakultas')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('prodi_id')
                    ->label('Prodi')
                    ->relationship('prodi', 'nama_unit')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'AKTIF' => 'Aktif',
                        'CUTI' => 'Cuti',
                        'LULUS' => 'Lulus',
                        'KELUAR' => 'Keluar',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            
            ->emptyStateHeading('Tidak Ada Data Akun Mahasiswa')
            ->emptyStateDescription('');
    }
}
