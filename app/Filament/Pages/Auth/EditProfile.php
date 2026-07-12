<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Support\Components\Component;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nama_lengkap')
                    ->required()
                    ->maxLength(255),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
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
