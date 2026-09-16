<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class ListSurats extends ListRecords
{
    protected static string $resource = SuratResource::class;

    public string $scope = 'keluar'; // or whatever default

    protected $queryString = [
        'scope' => ['except' => ''],
    ];

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => $this->scope]) => $this->getTitle(),
            '#' => 'List Surat',
        ];
    }
    public function getTitle(): string
    {
        return match ($this->scope) {
            'keluar' => 'Surat Keluar',
            'draft'  => 'Draft Surat',
            'arsip'  => 'Arsip Surat',
            default  => 'Semua Surat',
        };
    }



    public string $newKategoriNama = '';
    public ?int $editingKategoriId = null;
    public string $editingKategoriNama = '';

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label("Buat Surat Baru")->visible(fn() => $this->scope !== 'arsip'),
            \Filament\Actions\Action::make('manageKategoriArsip')
                ->label('Kelola Kategori Arsip')
                ->icon('heroicon-o-folder')
                ->color('gray')
                ->visible(fn() => $this->scope === 'arsip')
                ->modalHeading('Kelola Kategori Arsip Unit')
                ->modalDescription('Tambah, ubah nama, atau hapus kategori arsip untuk unit kerja Anda.')
                ->modalContent(fn() => view('filament.pages.staf-unit.manage-kategori-arsip-modal', [
                    'editingKategoriId' => $this->editingKategoriId,
                    'editingKategoriNama' => $this->editingKategoriNama,
                    'newKategoriNama' => $this->newKategoriNama,
                    'kategoriList' => $this->kategoriList,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    public function addKategori(): void
    {
        $unitId = Auth::user()?->unit_kerja_id;
        if (!$unitId || blank($this->newKategoriNama)) {
            return;
        }

        $nama = trim($this->newKategoriNama);

        $exists = \App\Models\KategoriArsip::where('unit_kerja_id', $unitId)
            ->where('nama', $nama)
            ->exists();

        if ($exists) {
            \Filament\Notifications\Notification::make()
                ->title('Kategori sudah ada')
                ->body('Kategori dengan nama tersebut sudah terdaftar di unit Anda.')
                ->danger()
                ->send();
            return;
        }

        \App\Models\KategoriArsip::create([
            'unit_kerja_id' => $unitId,
            'nama' => $nama,
        ]);

        $this->newKategoriNama = '';

        \Filament\Notifications\Notification::make()
            ->title('Kategori berhasil ditambahkan')
            ->success()
            ->send();
    }

    public function startEditKategori(int $id, string $nama): void
    {
        $this->editingKategoriId = $id;
        $this->editingKategoriNama = $nama;
    }

    public function cancelEditKategori(): void
    {
        $this->editingKategoriId = null;
        $this->editingKategoriNama = '';
    }

    public function saveEditKategori(): void
    {
        if (!$this->editingKategoriId || blank($this->editingKategoriNama)) {
            return;
        }

        $unitId = Auth::user()?->unit_kerja_id;
        $nama = trim($this->editingKategoriNama);

        $exists = \App\Models\KategoriArsip::where('unit_kerja_id', $unitId)
            ->where('nama', $nama)
            ->where('id', '!=', $this->editingKategoriId)
            ->exists();

        if ($exists) {
            \Filament\Notifications\Notification::make()
                ->title('Nama kategori sudah digunakan')
                ->danger()
                ->send();
            return;
        }

        \App\Models\KategoriArsip::where('id', $this->editingKategoriId)
            ->where('unit_kerja_id', $unitId)
            ->update(['nama' => $nama]);

        $this->editingKategoriId = null;
        $this->editingKategoriNama = '';

        \Filament\Notifications\Notification::make()
            ->title('Kategori berhasil diperbarui')
            ->success()
            ->send();
    }

    public function deleteKategori(int $id): void
    {
        $unitId = Auth::user()?->unit_kerja_id;
        $kategori = \App\Models\KategoriArsip::where('id', $id)
            ->where('unit_kerja_id', $unitId)
            ->withCount('arsipSurats')
            ->first();

        if (!$kategori) {
            return;
        }

        if ($kategori->arsip_surats_count > 0) {
            \Filament\Notifications\Notification::make()
                ->title('Kategori tidak dapat dihapus')
                ->body('Masih terdapat ' . $kategori->arsip_surats_count . ' surat yang menggunakan kategori ini.')
                ->danger()
                ->send();
            return;
        }

        $kategori->delete();

        \Filament\Notifications\Notification::make()
            ->title('Kategori berhasil dihapus')
            ->success()
            ->send();
    }

    public function getKategoriListProperty()
    {
        $unitId = Auth::user()?->unit_kerja_id;
        if (!$unitId) return collect();

        return \App\Models\KategoriArsip::where('unit_kerja_id', $unitId)
            ->withCount('arsipSurats')
            ->orderBy('nama')
            ->get();
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder | \Illuminate\Database\Eloquent\Relations\Relation | null
    {
        $query = static::getResource()::getEloquentQuery();
        $unitId = \Illuminate\Support\Facades\Auth::user()?->unit_kerja_id;

        return match ($this->scope) {
            'persetujuan' => $query
                ->whereIn('status_surat', ['DIPROSES', 'TERKIRIM'])
                ->whereHas('riwayats', function ($q) use ($unitId) {
                    $q->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', $unitId);
                }),

            'draft' => $query
                ->where('unit_pengirim_id', $unitId)
                ->where('status_surat', 'DRAFT'),

            'keluar' => $query
                ->where(function ($q) use ($unitId) {
                    $q->where('unit_pengirim_id', $unitId)

                    // manipulate this to deactivate the disposition letter from appearing in surat keluar - Seta
                        ->orWhereHas('disposisis', function ($dq) use ($unitId) {
                            $dq->whereHas('userPegawaiJabatan', function ($qJabatan) use ($unitId) {
                                $qJabatan->where('unit_kerja_id', $unitId);
                            });
                        });
                })
                ->where('status_surat', '!=', 'DRAFT')
                ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                    $q->where('unit_kerja_id', $unitId);
                }),

            'arsip' => app(\App\Services\UnitAksesService::class)
                ->applyArsipFilter($query, \Illuminate\Support\Facades\Auth::user(), (int) $unitId)
                ->with(['arsipSurats' => fn($q) => $q->where('unit_kerja_id', $unitId), 'arsipSurats.kategoriArsip']),

            'pengajuan' => $query
                ->where('tipe_surat', 'PENGAJUAN')
                ->where('status_surat', '!=', 'DRAFT')
                ->whereNull('terbitan_for_surat_id'),

            default => $query
                ->where('unit_pengirim_id', $unitId),
        };
    }
}
