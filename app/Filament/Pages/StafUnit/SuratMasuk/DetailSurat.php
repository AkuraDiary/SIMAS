<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\SuratUnit;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;
use App\Filament\Resources\Surats\SuratResource;
use App\Models\ArsipSurat;
use App\Models\Disposisi;
use App\Models\KategoriArsip;
use App\Models\Surat;
use App\Models\UnitKerja;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use PhpOffice\PhpWord\IOFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use PhpOffice\PhpWord\Settings;
use Illuminate\Validation\Rule;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class DetailSurat extends Page implements HasForms
{

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->tipe_entitas, ['ADMIN', 'STAF']);
    }

    use InteractsWithForms;
    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat}';

    protected static bool $shouldRegisterNavigation = false;


    public function getBreadcrumbs(): array
    {
        return match ($this->scope) {
            'arsip' => [
                SuratResource::getUrl('index', ['scope' => 'arsip']) => 'Arsip Surat',
                '#' => $this->surat->perihal,
                'Detail',
            ],
            'keluar' => [
                SuratResource::getUrl('index', ['scope' => 'keluar']) => 'Surat Keluar',
                '#' => $this->surat->perihal,
                'Detail',
            ],
            'persetujuan' => [
                SuratResource::getUrl('index', ['scope' => 'persetujuan']) => 'Persetujuan Surat',
                '#' => $this->surat->perihal,
                'Detail',
            ],
            default => [
                SuratMasuk::getUrl() => 'Surat Masuk',
                '#' => $this->surat->perihal,
                'Detail',
            ],
        };
    }
    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return view('filament.pages.staf-unit.surat-masuk.heading-detail', [
            'surat' => $this->surat
        ]);
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        // Suppress native subheading since we built a combined title block
        return null;
    }



    public Surat $surat;
    public ?SuratUnit $suratUnit = null;
    public ?string $jenisTujuanLabel = null;
    public $userUnitId = null;
    public ?string $renderedHtml = null;

    public string $scope = 'masuk';

    public function mount(Surat $surat): void
    {
        $this->userUnitId = Auth::user()->unit_kerja_id;
        $this->scope = request('scope', 'masuk');

        $this->surat = $surat->load([
            'template',
            'unitPengirim',
            'userPegawaiJabatan.pegawai',
            'userPegawaiJabatan.jabatan',
            'userPegawaiJabatan.unitKerja',
            'suratUnits' => function ($q) {
                if ($this->scope === 'masuk') {
                    $q->where('unit_kerja_id', $this->userUnitId);
                }
            },
            'disposisis',
            'disposisis.pembuat.jabatanAktif.unitKerja',
            'disposisis.unitTujuan',
        ]);

        $this->suratUnit = $this->surat->suratUnits->first();

        if ($this->scope === 'masuk' && $this->suratUnit && $this->suratUnit->status_baca === 'BELUM') {
            $this->suratUnit->update([
                'status_baca' => 'SUDAH',
                'tanggal_terima' => now()
            ]);
        }

        $this->jenisTujuanLabel = $this->resolveJenisTujuanLabel();

        if ($this->surat->template_id && $this->surat->template) {
            $service = app(\App\Services\PlaceholderService::class);
            $this->renderedHtml = $service->renderHtml($this->surat->template, $this->surat->content ?? []);
        } else {
            $this->renderedHtml = $this->surat->isi_surat;
        }
    }

    protected function getHeaderActions(): array
    {
        $primaryActions = [];
        $secondaryActions = [];
        $unitId = \Illuminate\Support\Facades\Auth::user()->unit_kerja_id;

        // Export (Always visible in Lainnya)
        $secondaryActions[] = Action::make('export')
            ->label('Export Surat')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn() => redirect()->route('surat.export', $this->surat));

        // 1. Arsipkan (If not draft)
        if ($this->surat->status_surat !== 'DRAFT') {
            $secondaryActions[] = $this->getActionArsipkan();
        }

        // 2. Persetujuan & Terbitan (For PENGAJUAN)
        if ($this->surat->tipe_surat === 'PENGAJUAN') {
            $hasPendingPersetujuan = $this->surat->riwayats()
                ->where('status', 'MENUNGGU')
                ->where('unit_tujuan_id', $unitId)
                ->exists();

            $hasRiwayats = $this->surat->riwayats()

                ->where('unit_tujuan_id', $unitId)
                ->exists();
            $persetujuan = $this->getActionPersetujuan();

            if ($hasPendingPersetujuan || !$hasRiwayats) {
                if (isset($persetujuan[0])) $primaryActions[] = $persetujuan[0]; // Setujui
                if (isset($persetujuan[1])) $secondaryActions[] = $persetujuan[1]; // Minta Revisi
                if (isset($persetujuan[2])) $secondaryActions[] = $persetujuan[2]; // Tolak
            }

            if (isset($persetujuan[3])) $primaryActions[] = $persetujuan[3]; // Buat Terbitan
        }

        // 3. Disposisi (Visibilities are handled inside the actions via canDisposisi / canRespondDisposisi)
        $disposisi = $this->getActionDisposisi();
        if (isset($disposisi[0])) $secondaryActions[] = $disposisi[0]; // Disposisikan
        if (isset($disposisi[1])) $primaryActions[] = $disposisi[1]; // Tindaklanjuti

        $actions = $primaryActions;

        if (count($secondaryActions) > 0) {
            $actions[] = ActionGroup::make($secondaryActions)
                ->label('Lainnya')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray');
        }

        return $actions;
    }

    protected function getActionDisposisi(): array
    {
        return [
            Action::make('disposisi')
                ->label('Disposisikan')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->visible(fn() => $this->canDisposisi())
                ->schema($this->getDisposisiForm())
                ->model(Disposisi::class)
                ->action(function (array $data, Action $action) {
                    return $this->handleDisposisi($data, $action);
                }),

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


            SpatieMediaLibraryFileUpload::make('bukti')
                ->label("Bukti Disposisi (Max 5MB)")
                ->multiple(false)
                ->dehydrated(true)
                ->image()
                ->collection('bukti-disposisi')
                ->preserveFilenames()
                ->maxSize(5048)
                ->required(),

            Textarea::make('catatan')
                ->label('Catatan')
                ->rows(4),
        ];
    }

    protected function getActionPersetujuan(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui / TTD')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan Persetujuan (Opsional)')
                        ->placeholder('Catatan atau catatan persetujuan...'),
                ])
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->approveStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        isFinalStep: true,
                        isSignatureRequired: true,
                        catatan: $data['catatan'] ?? null
                    );

                    $this->refreshPage('Berhasil', 'Surat berhasil disetujui & ditandatangani.');
                }),
            Action::make('reject')
                ->label('Minta Revisi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Alasan Revisi')
                        ->required()
                        ->placeholder('Jelaskan bagian yang perlu diperbaiki...'),
                ])
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        newStatus: 'REVISI',
                        catatan: $data['catatan']
                    );

                    $this->refreshPage('Berhasil', 'Surat dikembalikan untuk revisi.');
                }),
            Action::make('tolak_persetujuan')
                ->label('Tolak Persetujuan')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->placeholder('Jelaskan mengapa surat ini ditolak...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Tolak Surat Pengajuan')
                ->modalDescription('Apakah Anda yakin ingin menolak surat pengajuan ini? Surat akan dibatalkan.')
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        newStatus: 'DITOLAK',
                        catatan: $data['catatan']
                    );

                    $this->refreshPage('Berhasil', 'Surat pengajuan berhasil ditolak dan dibatalkan.');
                }),
            Action::make('buat_terbitan')
                ->label('Terbitkan Surat Balasan')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(
                    fn() => $this->surat->tipe_surat === 'PENGAJUAN' &&
                        in_array($this->surat->status_surat, ['DIPROSES', 'SELESAI']) &&

                        Auth::user()->unit_kerja_id !== $this->surat->unit_pengirim_id // user BUKAN pengirim surat pengajuan itu sendiri
                )
                ->url(fn() => \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl(['terbitan_for_surat_id' => $this->surat->id, 'tipe_surat' => 'TERBITAN']))
                ->openUrlInNewTab(),
        ];
    }
    protected function getActionArsipkan()
    {
        return Action::make('arsipkan')
            ->label('Arsipkan')
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->visible(fn() => ! $this->sudahDiarsipkan())
            ->schema([
                Select::make('kategori_arsip_id')
                    ->label('Kategori Arsip')
                    ->options(
                        KategoriArsip::where('unit_kerja_id', Auth::user()->unit_kerja_id)
                            ->pluck('nama', 'id')
                    )
                    ->searchable()
                    ->required()->createOptionForm([
                        TextInput::make('nama')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(100)
                            ->rule(function () {
                                return Rule::unique('kategori_arsips', 'nama')
                                    ->where('unit_kerja_id', Auth::user()->unit_kerja_id);
                            }),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return KategoriArsip::create([
                            'unit_kerja_id' => Auth::user()->unit_kerja_id,
                            'nama' => $data['nama'],
                        ])->id;
                    }),

                Textarea::make('catatan')
                    ->label('Catatan')
                    ->rows(3),
            ])
            ->action(fn($data) => $this->handleArsipkanSurat($data));
    }

    protected function getResponSuratUnitForm(): array
    {
        return [];
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

        if ($data['status_disposisi'] === 'SELESAI') {
            $pembuat = $disposisi->pembuat;
            if ($pembuat) {
                Notification::make()
                    ->title('Disposisi Selesai')
                    ->body("Unit " . Auth::user()->pegawai?->jabatanAktif()?->first()?->unitKerja?->nama_unit . " telah menyelesaikan disposisi pada surat: " . $this->surat->perihal)
                    ->success()
                    ->sendToDatabase($pembuat);
            }
        }

        $this->updateStatusSurat();

        $this->refreshPage('Disposisi diperbarui', null);
    }
    protected function handleResponSuratUnit(array $data): void {}

    protected function handleDisposisi(array $data, Action $action): void
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

        $skipped = [];
        $successCount = 0;

        foreach ($data['unit_tujuan_ids'] as $unitTujuanId) {

            $alreadyExists = Disposisi::where('surat_id', $this->surat->id)
                ->where('unit_tujuan_id', $unitTujuanId)
                ->exists();

            if ($alreadyExists) {
                $unitName = \App\Models\UnitKerja::find($unitTujuanId)?->nama_unit ?? 'Unit';
                $skipped[] = $unitName;
                continue;
            }


            $disposisi = Disposisi::create([
                'surat_id' => $this->surat->id,
                'unit_tujuan_id' => $unitTujuanId,
                'user_pembuat_id' => Auth::id(),
                'jenis_instruksi' => $jenisInstruksi,
                'sifat' => $data['sifat'],
                'catatan' => $data['catatan'],
                'status_disposisi' => 'BARU',
                'tanggal_disposisi' => now(),
                'parent_disposisi_id' => $parentDisposisi?->id,
            ]);
            if (!empty($data['bukti'])) {
                $disposisi
                    ->addMedia($data['bukti'])
                    ->toMediaCollection('bukti-disposisi');
            }
            $successCount++;
        }

        if ($successCount > 0) {
            $this->surat->update([
                'status_surat' => 'DIPROSES',
            ]);
        }

        if (count($skipped) > 0 && $successCount > 0) {
            $this->refreshPage('Disposisi berhasil sebagian', 'Berhasil didisposisikan, namun unit berikut dilewati karena sudah menerima: ' . implode(', ', $skipped));
        } elseif (count($skipped) > 0 && $successCount === 0) {
            Notification::make()->title('Disposisi ditolak')->body('Semua unit tujuan sudah pernah menerima disposisi untuk surat ini.')->danger()->send();
        } else {
            $this->refreshPage('Disposisi berhasil', 'Surat telah berhasil didisposisikan.');
        }
    }
    protected function handleArsipkanSurat(array $data): void
    {
        ArsipSurat::create([
            'surat_id' => $this->surat->id,
            'unit_kerja_id' => Auth::user()->unit_kerja_id,
            'kategori_arsip_id' => $data['kategori_arsip_id'],
            'catatan' => $data['catatan'] ?? null,
        ]);

        $this->refreshPage('Surat diarsipkan', 'Surat berhasil masuk arsip unit.');
    }

    // Handler methods


    // Helper methods

    public ?string $previewHtml = null;
    public ?string $previewUrl = null;
    public ?string $downloadUrl = null;
    public bool $previewModal = false;


    public function openPreview(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        if (str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf') {

            $this->previewUrl = route('media.file', $media->id);
            $this->downloadUrl = route('media.download', $media->id);
        } elseif ($media->getCustomProperty('preview_media_id')) {

            $previewMedia = Media::find($media->getCustomProperty('preview_media_id'));

            $this->previewUrl = route('media.file', $previewMedia->id);
            $this->downloadUrl = route('media.download', $media->id); // download original

        } else {

            $this->previewUrl = null;
            $this->downloadUrl = route('media.download', $media->id);
        }

        $this->dispatch('open-modal', id: 'preview-modal');
    }

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

    protected function sudahDiarsipkan(): bool
    {
        return ArsipSurat::where('surat_id', $this->surat->id)
            ->where('unit_kerja_id', Auth::user()->unit_kerja_id)
            ->exists();
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
            'UTAMA' => 'Tujuan Utama',
            'TEMBUSAN' => 'Tembusan',
            default => '-',
        };
    }
}
