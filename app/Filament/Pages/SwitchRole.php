<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SwitchRole extends Page
{
    protected static string| BackedEnum |null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'Ganti Peran (Unit)';
    protected static ?string $title = 'Ganti Peran Aktif';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static bool $shouldRegisterNavigation = false;


    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas !== 'ADMIN';
    }
    protected string $view = 'filament.pages.switch-role';

    public $activeJabatanId;
    public $jabatans = [];

    public function mount()
    {
        $user = Auth::user();
        if ($user->pegawai) {
            $this->jabatans = $user->pegawai->jabatanAktif()->with(['jabatan', 'unitKerja'])->get();
        }
        $this->activeJabatanId = session('active_jabatan_id', $this->jabatans->first()?->id);
    }

    public function switchRole($id)
    {
        session(['active_jabatan_id' => $id]);

        $user = Auth::user();
        if ($user) {
            $settings = $user->settings ?? [];
            $settings['last_active_jabatan_id'] = (int) $id;
            $user->settings = $settings;
            $user->save();
        }

        \Filament\Notifications\Notification::make()
            ->title('Peran berhasil diganti!')
            ->success()
            ->send();

        return redirect()->to('/internal/switch-role');
    }
}
