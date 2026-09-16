<?php

namespace App\Filament\Pages\StafUnit;

use App\Models\UnitKerja;
use App\Models\UserPegawaiJabatan;
use App\Services\UnitAksesService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageAksesUnit extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;
    protected static ?string $navigationLabel = 'Akses Surat Unit';
    protected static ?string $title = 'Manajemen Akses Surat Unit';
    protected static ?string $slug = 'pengaturan-akses-unit';
    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.staf-unit.manage-akses-unit';

    public ?int $unitId = null;
    public ?string $unitNama = null;
    public string $kebijakanSuratMasuk = 'TERBUKA';
    public int $minLevelJabatan = 1;



    public array $staffList = [];
    public array $staffPermissions = [];

    /**
     * Policy authorization: Only STAF who is Kepala Unit (or ADMIN) can access this page.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->tipe_entitas === 'ADMIN') {
            return false;
        }

        if ($user->tipe_entitas !== 'STAF') {
            return false;
        }

        return $user->isKepalaUnit();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->unitId = $user->unit_kerja_id;

        if (!$this->unitId && $user->tipe_entitas === 'ADMIN') {
            $this->unitId = UnitKerja::where('is_active', true)->first()?->id;
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        if (!$this->unitId) {
            return;
        }

        $unit = UnitKerja::find($this->unitId);
        if (!$unit) {
            return;
        }

        $this->unitNama = $unit->nama_unit;
        $this->kebijakanSuratMasuk = $unit->getKebijakanSuratMasuk();
        $this->minLevelJabatan = $unit->getMinLevelJabatan();

        // Load all active staff in this unit
        $staffMembers = UserPegawaiJabatan::with(['pegawai.user', 'jabatan'])
            ->where('unit_kerja_id', $this->unitId)
            ->where('status_jabatan', 'AKTIF')
            ->get()
            ->sortBy(fn($item) => $item->jabatan?->level_jabatan ?? 99);

        $this->staffList = [];
        $this->staffPermissions = [];

        foreach ($staffMembers as $staff) {
            $isKepala = (int) ($staff->jabatan?->level_jabatan ?? 99) === 1;

            $this->staffList[] = [
                'id' => $staff->id,
                'user_id' => $staff->pegawai?->user?->id,
                'nama' => $staff->pegawai?->nama_lengkap ?? $staff->pegawai?->user?->username ?? '-',
                'nip' => $staff->pegawai?->nip ?? '-',
                'nama_jabatan' => $staff->jabatan?->nama_jabatan ?? 'Staf',
                'level_jabatan' => $staff->jabatan?->level_jabatan ?? 99,
                'is_kepala' => $isKepala,
            ];

            $this->staffPermissions[$staff->id] = [
                'akses_surat_masuk' => $staff->akses_surat_masuk ?? 'DEFAULT',
                'can_disposisi'     => (bool) $staff->can_disposisi,
            ];
        }
    }

    public function saveUnitPolicy(): void
    {
        if (!$this->unitId) {
            return;
        }

        $unit = UnitKerja::find($this->unitId);
        if (!$unit) {
            return;
        }

        app(UnitAksesService::class)->updateUnitSettings($unit, [
            'kebijakan_surat_masuk' => $this->kebijakanSuratMasuk,
            'min_level_jabatan'     => (int) $this->minLevelJabatan,
        ]);

        Notification::make()
            ->title('Kebijakan Unit Disimpan')
            ->body('Kebijakan visibilitas surat masuk untuk unit ' . $unit->nama_unit . ' berhasil diperbarui.')
            ->success()
            ->send();
    }

    public function saveStaffPermissions(): void
    {
        $service = app(UnitAksesService::class);

        foreach ($this->staffPermissions as $staffId => $perms) {
            $service->updateStaffPermissions((int) $staffId, $perms);
        }

        Notification::make()
            ->title('Hak Akses Pegawai Disimpan')
            ->body('Pengaturan hak akses dan delegasi pegawai berhasil diperbarui.')
            ->success()
            ->send();
    }
}
