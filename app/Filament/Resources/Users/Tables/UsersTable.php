<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\UnitKerja;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('pegawai.nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('tipe_entitas')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'STAF' => 'Staf Unit',
                        default => $state,
                    }),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
                TextColumn::make('jabatanAktif.unitKerja.nama_unit')
                    ->label('Unit Kerja')
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
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ])
                    ->label('Status User'),
                Filter::make('unit_kerja')
                    ->schema([
                        \Filament\Forms\Components\Select::make('unit_kerja_id')
                            ->label('Filter Unit Kerja')
                            ->options(fn() => UnitKerja::query()->pluck('nama_unit', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['unit_kerja_id'] ?? null,
                            fn($q, $unitId) => $q->whereHas(
                                'jabatanAktif',
                                fn($jq) => $jq->where('unit_kerja_id', $unitId)
                            )
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Tidak Ada Data Staf')
            ->emptyStateDescription('');
    }
}
