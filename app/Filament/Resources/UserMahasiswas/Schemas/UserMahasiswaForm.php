<?php

namespace App\Filament\Resources\UserMahasiswas\Schemas;

use App\Models\UnitKerja;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserMahasiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Mahasiswa')
                    ->description('Data pokok Mahasiswa.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('nim')
                            ->required()
                            ->unique(
                                table: 'user_mahasiswa',
                                column: 'nim',
                                modifyRuleUsing: fn($rule, $record) => $rule->withoutTrashed()->ignore($record?->id)
                            )
                            ->rules([
                                fn($record) => \Illuminate\Validation\Rule::unique('users', 'username')
                                    ->withoutTrashed()
                                    ->ignore($record?->user_id),
                            ])
                            ->validationMessages([
                                'unique' => 'NIM ini sudah terdaftar pada data mahasiswa aktif.',
                            ])
                            ->disabled(fn(string $context): bool => $context === 'edit')
                            ->dehydrated(),
                        TextInput::make('nama_lengkap')
                            ->required(),
                        DatePicker::make('tanggal_lahir')
                            ->native(false)
                            ->displayFormat('d F Y'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Informasi Akademik')
                    ->description('Status dan penempatan program studi mahasiswa saat ini.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        TextInput::make('tahun_masuk')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue((int) date('Y') + 1),
                        Select::make('status')
                            ->options([
                                'AKTIF' => 'Aktif',
                                'CUTI' => 'Cuti',
                                'LULUS' => 'Lulus',
                                'KELUAR' => 'Keluar',
                            ])
                            ->default('AKTIF')
                            ->required()
                            ->native(false),
                        Select::make('fakultas_id')
                            ->label('Fakultas')
                            ->relationship(
                                name: 'fakultas',
                                titleAttribute: 'nama_unit',
                                modifyQueryUsing: fn(Builder $query) => $query
                                    ->whereHas('jenisUnit', fn(Builder $q) => $q->where('nama_jenis', 'Fakultas'))
                                    ->orderBy('nama_unit')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateHydrated(function (Select $component, $state, $record): void {
                                if (blank($state) && $record?->prodi?->parent_id) {
                                    $component->state($record->prodi->parent_id);
                                }
                            })
                            ->afterStateUpdated(fn(Set $set) => $set('prodi_id', null)),
                        Select::make('prodi_id')
                            ->label('Prodi')
                            ->options(function (Get $get): array {
                                $fakultasId = $get('fakultas_id');
                                if (blank($fakultasId)) {
                                    return [];
                                }

                                return UnitKerja::query()
                                    ->where('parent_id', $fakultasId)
                                    ->whereHas('jenisUnit', fn(Builder $q) => $q->where('nama_jenis', 'Prodi'))
                                    ->orderBy('nama_unit')
                                    ->pluck('nama_unit', 'id')
                                    ->all();
                            })
                            ->disabled(fn(Get $get): bool => blank($get('fakultas_id')))
                            ->placeholder(fn(Get $get): string => blank($get('fakultas_id')) ? 'Pilih Fakultas terlebih dahulu' : 'Pilih Prodi')
                            ->searchable(),
                    ])
                    ->columnSpanFull()
                    ->columns(2),

                Section::make('Kontak')
                    ->description('Kontak Akun Mahasiswa')
                    ->icon('heroicon-o-at-symbol')
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'email',
                                modifyRuleUsing: fn($rule, $record) => $rule->withoutTrashed()->ignore($record?->user_id)
                            )
                            ->validationMessages([
                                'unique' => 'Email ini sudah digunakan oleh akun lain.',
                            ])
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->email)),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            // ->numeric()
                            ->minLength(10)
                            ->maxLength(14)
                            ->rule('regex:/^08[0-9]{8,12}$/')
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->phone)),
                        Select::make('opsi_aktivasi')
                            ->label('Pengiriman Link Aktivasi')
                            ->options([
                                'nanti'    => 'Kirim Nanti Saja (Default)',
                                'whatsapp' => 'Kirim via WhatsApp Sekarang',
                                'email'    => 'Kirim via Email Sekarang',
                            ])
                            ->default('nanti')
                            ->helperText('Pilih metode untuk mengirimkan link aktivasi saat akun dibuat.')
                            ->visible(fn(string $context): bool => $context === 'create')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }
}
