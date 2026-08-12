<?php

namespace App\Filament\Mahasiswa\Resources\Surats\Pages;

use App\Filament\Mahasiswa\Resources\Surats\SuratResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSurat extends CreateRecord
{
    protected static string $resource = SuratResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        $data['user_pembuat_id'] = $user->id;
        $data['tipe_surat'] = 'PENGAJUAN';
        $data['status_surat'] = 'DIPROSES';
        
        if ($user->mahasiswa) {
            $data['pengirim_nim'] = $user->mahasiswa->nim;
            $data['pengirim_nama'] = $user->mahasiswa->nama_lengkap;
            $data['pengirim_email'] = $user->email;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $surat = $this->record;
        $hash = substr(md5($surat->id . config('app.key') . 'PENGAJUAN'), 0, 8);
        $surat->tracking_code = "REQ-{$surat->id}-{$hash}";
        $surat->save();
    }
}
