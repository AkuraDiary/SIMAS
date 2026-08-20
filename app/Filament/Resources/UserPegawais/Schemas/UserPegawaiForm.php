<?php

namespace App\Filament\Resources\UserPegawais\Schemas;

use App\Models\UnitKerja;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserPegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Identitas Pegawai')
                    ->description('Data pokok Pegawai.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        // No user_id field — the linked User is provisioned automatically
                        // (username/password = NIP), see UserProvisioningService::createStaf().
                        TextInput::make('nip')
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'username',
                                modifyRuleUsing: fn ($rule, $record) => $record ? $rule->ignore($record->user_id) : $rule
                            )
                            ->disabled(fn(string $context): bool => $context === 'edit')
                            ->dehydrated(),
                        TextInput::make('nama_lengkap')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Kontak')
                    ->description('Kontak Akun Pegawai')
                    ->icon('heroicon-o-at-symbol')
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->email)),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->phone)),
                    ])
                    ->columnSpanFull()
                    ->columns(2),

                Section::make('Informasi Kepegawaian')
                    ->description('Status dan penempatan unit pegawai saat ini.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Repeater::make('jabatan_assignments')
                            ->label('Jabatan')
                            ->schema([
                                Select::make('unit_kerja_id')
                                    ->label('Unit Kerja')
                                    ->options(fn() => UnitKerja::query()->where('is_active', true)->pluck('nama_unit', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    // Trigger a re-render so jabatan_id options update
                                    ->live(),

                                Select::make('jabatan_id')
                                    ->label('Jabatan')
                                    // Options are filtered to the selected unit only.
                                    // Empty while no unit is selected.
                                    ->options(function (Get $get): array {
                                        $unitId = $get('unit_kerja_id');
                                        if (!$unitId) {
                                            return [];
                                        }
                                        return \App\Models\Jabatan::where('unit_kerja_id', $unitId)
                                            ->orderBy('level_jabatan')
                                            ->orderBy('nama_jabatan')
                                            ->pluck('nama_jabatan', 'id')
                                            ->all();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->helperText(fn(Get $get) => $get('unit_kerja_id')
                                        ? null
                                        : 'Pilih unit terlebih dahulu.'
                                    ),

                                Select::make('status_jabatan')
                                    ->label('Status')
                                    ->options([
                                        'AKTIF'    => 'Aktif',
                                        'NONAKTIF' => 'Nonaktif',
                                    ])
                                    ->default('AKTIF')
                                    ->required()
                                    ->native(false),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->addActionLabel('Tambah Jabatan')
                            ->defaultItems(1),
                    ])
                    ->columnSpanFull()
                    ->columns(2),



            ]);
    }
}
