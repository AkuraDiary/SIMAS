<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Components\Component;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Override;

class EditProfile extends BaseEditProfile
{



    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(), // Keeps the default "Save" button

            Action::make('home')
                ->label('Home')
                ->icon('heroicon-m-home') // Uses Heroicons
                ->url(fn(): string => url('/internal')),
        ];
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Profil & Kontak')
                    ->description('Perbarui informasi akun, email, dan nomor kontak Anda.')
                    ->schema([
                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255),

                        $this->getEmailFormComponent()
                            ->label('Alamat Email'),

                        TextInput::make('phone')
                            ->label('Nomor WhatsApp / HP')
                            ->tel()
                            ->placeholder('Contoh: 081234567890 atau 6281234567890')
                            ->helperText('Digunakan untuk pengiriman notifikasi alur surat melalui WhatsApp.')
                            ->maxLength(20),
                    ])
                    ->columns(2),
                Section::make('Keamanan & Kata Sandi')
                    ->description('Kosongkan bagian ini jika Anda tidak bermaksud mengubah kata sandi.')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->columnSpanFull(),
                Section::make('Preferensi Notifikasi WhatsApp')
                    ->description('Atur jenis notifikasi surat yang ingin Anda terima secara otomatis melalui pesan WhatsApp.')
                    ->visible(fn() => auth()->user()?->tipe_entitas !== 'ADMIN')
                    ->schema([
                        Toggle::make('settings.notifikasi_whatsapp')
                            ->label('Aktifkan Notifikasi via WhatsApp')
                            ->helperText('Aktifkan untuk menerima pembaruan status surat langsung ke WhatsApp Anda.')
                            ->live()
                            ->columnSpanFull(),

                        Toggle::make('settings.wa_notif_surat_masuk')
                            ->label('Surat Masuk & Disposisi Baru')
                            ->helperText('Notifikasi saat ada surat masuk atau lembar disposisi baru untuk unit Anda.')
                            ->default(true)
                            ->visible(fn(Get $get) => (bool) $get('settings.notifikasi_whatsapp')),

                        Toggle::make('settings.wa_notif_surat_revisi')
                            ->label('Surat Dikembalikan / Butuh Revisi')
                            ->helperText('Notifikasi saat surat yang diajukan dikembalikan untuk diperbaiki.')
                            ->default(true)
                            ->visible(fn(Get $get) => (bool) $get('settings.notifikasi_whatsapp')),

                        Toggle::make('settings.wa_notif_surat_selesai')
                            ->label('Surat Disetujui / Selesai / Terbitan')
                            ->helperText('Notifikasi saat surat disetujui, selesai diproses, atau surat terbitan dibuat.')
                            ->default(true)
                            ->visible(fn(Get $get) => (bool) $get('settings.notifikasi_whatsapp')),

                        Toggle::make('settings.wa_notif_surat_ditolak')
                            ->label('Surat Ditolak Permanen')
                            ->helperText('Notifikasi saat surat permohonan ditolak secara permanen.')
                            ->default(true)
                            ->visible(fn(Get $get) => (bool) $get('settings.notifikasi_whatsapp')),
                    ])
                    ->columns(2),

                Section::make('Preferensi Notifikasi Pop-up & Email')
                    ->description('Atur apakah notifikasi ditampilkan sebagai pop-up melayang di layar dan dikirim ke surel.')
                    ->visible(fn() => auth()->user()?->tipe_entitas !== 'ADMIN')
                    ->schema([
                        Toggle::make('settings.notifikasi_popup')
                            ->label('Notifikasi Web (Pop-up)')
                            ->helperText('Tampilkan pop-up melayang saat ada pembaruan surat.')
                            ->default(true),

                        Toggle::make('settings.notifikasi_email')
                            ->label('Terima Notifikasi via Email')
                            ->helperText('Kirim salinan pemberitahuan penting ke alamat email akun Anda.')
                            ->default(true),
                    ])
                    ->columns(2),


            ]);
    }

    /**
     * nama_lengkap is a computed accessor on User (identity now lives on
     * user_pegawai/user_mahasiswa), so it has to be written through manually
     * rather than relying on a plain $record->update($data).
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $namaLengkap = $data['nama_lengkap'] ?? null;
        unset($data['nama_lengkap']);

        // Merge settings agar tidak menimpa setting yang sudah ada sebelumnya (seperti last_active_jabatan_id)
        if (isset($data['settings']) && is_array($data['settings'])) {
            $existingSettings = $record->settings ?? [];
            $data['settings'] = array_merge($existingSettings, $data['settings']);
        }

        $record->update($data);

        if (filled($namaLengkap)) {
            if ($record->pegawai) {
                $record->pegawai->update(['nama_lengkap' => $namaLengkap]);
            } elseif ($record->mahasiswa) {
                $record->mahasiswa->update(['nama_lengkap' => $namaLengkap]);
            } elseif ($record->tipe_entitas === 'STAF') {
                $record->pegawai()->create(['nama_lengkap' => $namaLengkap]);
            }
        }

        return $record;
    }
}
