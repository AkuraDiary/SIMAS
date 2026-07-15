<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use App\Services\UserProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditUserPegawai extends EditRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected ?array $pendingData = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingData = [
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'unit_kerja_id' => $data['unit_kerja_id'] ?? null,
            'jabatan_id' => $data['jabatan_id'] ?? null,
        ];

        unset($data['email'], $data['phone'], $data['unit_kerja_id'], $data['jabatan_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->user?->update([
            'email' => $this->pendingData['email'],
            'phone' => $this->pendingData['phone'],
        ]);

        app(UserProvisioningService::class)->updateJabatanAktif(
            $this->record,
            $this->pendingData['unit_kerja_id'],
            $this->pendingData['jabatan_id']
        );
    }
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            Action::make('manageAccount')
                ->label('Kelola Akun')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->steps([
                    Step::make('Verifikasi')
                        ->schema([
                            TextInput::make('admin_password')
                                ->label('Password Anda (Admin)')
                                ->password()
                                ->revealable()
                                ->required()
                                ->helperText('Konfirmasi identitas Anda untuk melanjutkan.')
                                ->rules([
                                    fn() => function (string $attribute, $value, \Closure $fail) {
                                        if (! Hash::check($value, Auth::user()->password)) {
                                            $fail('Password salah.');
                                        }
                                    },
                                ]),
                        ]),

                    Step::make('Edit Akun')
                        ->schema([
                            TextInput::make('username')
                                ->required()
                                ->default(fn() => $this->record->user?->username)
                                ->unique(
                                    table: 'users',
                                    column: 'username',
                                    ignorable: fn() => $this->record->user
                                ),

                            TextInput::make('email')
                                ->email()
                                ->default(fn() => $this->record->user?->email)
                                ->unique(
                                    table: 'users',
                                    column: 'email',
                                    ignorable: fn() => $this->record->user
                                ),

                            TextInput::make('phone')
                                ->label('Nomor Telepon')
                                ->tel()
                                ->default(fn() => $this->record->user?->phone),

                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                            TextInput::make('new_password_confirmation')
                                ->label('Konfirmasi Password Baru')
                                ->password()
                                ->revealable()
                                ->same('new_password')
                                ->visible(fn($get) => filled($get('new_password'))),
                        ]),
                ])
                ->action(function (array $data) {
                    $user = $this->record->user;

                    if (! $user) {
                        Notification::make()
                            ->title('Akun pengguna tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    $updates = [
                        'username' => $data['username'],
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                    ];

                    if (filled($data['new_password'])) {
                        $updates['password'] = Hash::make($data['new_password']);
                    }

                    $user->update($updates);

                    Notification::make()
                        ->title('Kredensial akun berhasil diperbarui')
                        ->success()
                        ->send();
                }),
                
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
