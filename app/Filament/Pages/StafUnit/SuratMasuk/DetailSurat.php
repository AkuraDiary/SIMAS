<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\SuratUnit;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;
use App\Filament\Resources\Surats\SuratResource;
use App\Models\Disposisi;
use App\Models\Surat;
use App\Models\UnitKerja;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Request;
use Filament\Notifications\Notification;

class DetailSurat extends Page
{
    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat}';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            $this->isViewKirim ? SuratResource::getUrl('index', ['scope' => 'keluar']) : SuratMasuk::getUrl() => $this->isViewKirim ? 'Surat Keluar' : 'Surat Masuk',
            '#' => $this->surat->nomor_surat,
            'Detail',
        ];
    }

    public Surat $surat;
    public ?SuratUnit $suratUnit = null;
    public ?string $jenisTujuanLabel = null;
    public $userUnitId = null;
    public $isViewKirim = false;

    public function mount(Surat $surat): void
    {

        $this->userUnitId = Auth::user()->unit_kerja_id;
        $this->isViewKirim = Request::boolean('isViewKirim', false);
        // dd(! $this->isViewKirim);
        $this->surat = $surat->load([
            'unitPengirim',
            'suratUnits' => function ($q) {
                if (!$this->isViewKirim) {
                    $q->where('unit_kerja_id', $this->userUnitId);
                }
                // else: ambil semua unit, tidak perlu filter
            },
            'disposisis',
            'disposisis.unitPembuat',
            'disposisis.unitTujuan',
        ]);

        // Ambil SuratUnit jika ada (langsung)

        $this->suratUnit = $this->surat->suratUnits->first();

        // Mark read ONLY if lewat surat_unit
        if ($this->suratUnit && $this->suratUnit->status_baca === 'BELUM') {
            $this->suratUnit->update(['status_baca' => 'SUDAH']);
        }

        $this->jenisTujuanLabel = $this->resolveJenisTujuanLabel();
    }

    protected function getHeaderActions(): array
    {
        return $this->isViewKirim ? [
            $this->getACtionArsipkan()
        ] :  [


            Action::make('respon_surat_unit')
                ->label('Respon')
                ->color('success')

                ->schema($this->getResponSuratUnitForm())
                ->action(fn(array $data) => $this->handleResponSuratUnit($data)),

            Action::make('disposisi')
                ->label('Disposisikan')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->visible(fn() => $this->canDisposisi())
                ->schema($this->getDisposisiForm())
                ->action(fn(array $data) => $this->handleDisposisi($data)),

            Action::make('respon_disposisi')
                ->label('Tindaklanjuti Disposisi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->canRespondDisposisi())
                ->schema([
                    Select::make('status_disposisi')
                        ->label('Status')
                        ->options([
                            'DIPROSES' => 'Sedang Diproses',
                            'SELESAI' => 'Selesai',
                        ])
                        ->required(),

                    Textarea::make('catatan_respon')
                        ->label('Catatan Tindak Lanjut')
                        ->rows(3),
                ])
                ->action(fn(array $data) => $this->handleRespondDisposisi($data)),

        ];
    }

    protected function getDisposisiForm(): array
    {
        return [
            Select::make('unit_tujuan_ids')
                ->label('Tujuan Disposisi')
                ->options(
                    UnitKerja::query()->where('id', '<>', Auth::user()->unit_kerja_id)
                        ->pluck('nama_unit', 'id')
                )
                ->searchable()
                ->multiple()
                ->required(),

            Select::make('jenis_instruksi')
                ->label('Jenis Instruksi')
                ->options([
                    'tindaklanjuti' => 'Tindak lanjuti',
                    'koordinasikan' => 'Koordinasikan',
                    'laporkan' => 'Laporkan',
                    'arsipkan' => 'Arsipkan',
                    'saran' => 'Ajukan Pendapat / Saran',
                    'diketahui' => 'Untuk diperhatikan / diketahui',
                    'laporan' => 'Laporan / Laporkan',
                    'acc' => 'Setuju / ACC',
                    'pengecekan' => 'Adakan Pengecekan',
                    'mewakili' => 'Agar Mewakili',
                    'jawab' => 'Siapkan Jawaban',
                    'diselesaikan' => 'Untuk Diselesaikan',
                    'bahas' => 'Bahas Bersama',
                    'edarkan' => 'Gandakan / Edarkan',
                    'lainnya' => 'Instruksi Lainnya',
                ])
                ->reactive()
                ->required(),

            Textarea::make('instruksi_custom')
                ->label('Instruksi Khusus')
                ->rows(3)
                ->required(fn($get) => $get('jenis_instruksi') === 'lainnya')
                ->visible(fn($get) => $get('jenis_instruksi') === 'lainnya'),


            Select::make('sifat')
                ->options([
                    'rahasia' => 'Rahasia',
                    'penting' => 'Penting',
                    'biasa' => 'Biasa',
                    'segera' => 'Segera',
                    'sangat segera' => 'Sangat Segera',

                ])
                ->required(),

            Textarea::make('catatan')
                ->label('Catatan')
                ->rows(4),
        ];
    }
    protected function getResponSuratUnitForm(): array
    {
        return [];
    }
    protected function getActionArsipkan()
    {
        return Action::make('arsipkan_surat')
            ->action(fn(array $data) => $this->handleArsipkanSurat($data));
    }
    // Handler methods
    protected function handleRespondDisposisi(array $data): void
    {
        $unitId = Auth::user()->unit_kerja_id;

        $disposisi = $this->surat->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->sortByDesc('tanggal_disposisi')
            ->first();

        if (! $disposisi) {
            abort(403);
        }

        $disposisi->update([
            'status_disposisi' => $data['status_disposisi'],
            'catatan' => trim(
                ($disposisi->catatan ?? '') .
                    "\n\nCatatan Tindak lanjut: " .
                    ($data['catatan_respon'] ?? '-')
            ),
        ]);

        $this->updateStatusSurat();

        $this->refreshPage('Disposisi diperbarui', null);
    }
    protected function handleResponSuratUnit(array $data): void {}

    protected function handleDisposisi(array $data): void
    {
        $user = Auth::user();
        $unitId = $user->unit_kerja_id;

        $parentDisposisi = $this->surat
            ->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->sortByDesc('tanggal_disposisi')
            ->first();

        $jenisInstruksi = $data['jenis_instruksi'] === 'lainnya'
            ? $data['instruksi_custom']
            : $data['jenis_instruksi'];



        foreach ($data['unit_tujuan_ids'] as $unitTujuanId) {

            $alreadyExists = Disposisi::where('surat_id', $this->surat->id)
                ->where('unit_tujuan_id', $unitTujuanId)
                ->exists();

            if ($alreadyExists) {
                Notification::make()
                    ->title('Disposisi ditolak')
                    ->body('Unit tujuan sudah pernah menerima disposisi untuk surat ini.')
                    ->danger()
                    ->send();

                return;
            }


            Disposisi::create([
                'surat_id' => $this->surat->id,
                'unit_tujuan_id' => $unitTujuanId,
                'unit_pembuat_id' => $unitId,
                'jenis_instruksi' => $jenisInstruksi,
                'sifat' => $data['sifat'],
                'catatan' => $data['catatan'],
                'status_disposisi' => 'BARU',
                'tanggal_disposisi' => now(),
                'parent_disposisi_id' => $parentDisposisi?->id,
            ]);
        }

        $this->surat->update([
            'status_surat' => 'DIPROSES',
        ]);

        $this->refreshPage('Disposisi berhasil', 'Surat telah berhasil didisposisikan.');
    }
    protected function handleArsipkanSurat(array $data): void {}

    // Handler methods


    // Helper methods

    protected function refreshPage(string $message, ?string $body): void
    {
        $this->surat->refresh();
        $this->mount($this->surat);

        Notification::make()
            ->title($message)
            ->body($body)
            ->success()
            ->send();
    }

    protected function canRespondDisposisi(): bool
    {
        $unitId = Auth::user()->unit_kerja_id;

        return $this->surat->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->where('status_disposisi', '!=', 'SELESAI')
            ->isNotEmpty();
    }

    protected function updateStatusSurat(): void
    {
        $allDone = $this->surat->disposisis->every(fn($d) => $d->status_disposisi === 'SELESAI');

        $this->surat->update([
            'status_surat' => $allDone ? 'SELESAI' : 'DIPROSES',
        ]);
    }

    protected function canDisposisi(): bool
    {
        $unitId = Auth::user()->unit_kerja_id;

        return $this->suratUnit !== null || $this->surat->disposisis->contains('unit_tujuan_id', $unitId);
    }

    protected function resolveJenisTujuanLabel(): string
    {
        $userUnitId = Auth::user()->unit_kerja_id;

        $disposisi = $this->surat
            ->disposisis
            ->firstWhere('unit_tujuan_id', $userUnitId);

        if ($disposisi) {
            $unitAsal = $disposisi->pembuat?->unitKerja?->nama_unit;
            return $unitAsal
                ? 'Disposisi dari ' . $unitAsal
                : 'Disposisi';
        }



        return match ($this->suratUnit?->jenis_tujuan) {
            'utama' => 'Tujuan Utama',
            'tembusan' => 'Tembusan',
            default => '-',
        };
    }
}
