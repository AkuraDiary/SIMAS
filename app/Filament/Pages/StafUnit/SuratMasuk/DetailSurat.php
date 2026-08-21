<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasApprovalActions;
use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasArsipActions;
use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasDisposisiActions;
use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasInternalActions;
use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasSuratTimeline;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;
use App\Filament\Resources\Surats\SuratResource;
use App\Models\Surat;
use App\Models\SuratUnit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DetailSurat extends Page implements HasForms
{
    use InteractsWithForms;
    use HasSuratTimeline;
    use HasDisposisiActions;
    use HasApprovalActions;
    use HasArsipActions;
    use HasInternalActions;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->tipe_entitas, ['ADMIN', 'STAF']);
    }

    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat}';
    protected static bool $shouldRegisterNavigation = false;

    public Surat $surat;
    public ?SuratUnit $suratUnit = null;
    public ?string $jenisTujuanLabel = null;
    public $userUnitId = null;
    public ?string $renderedHtml = null;
    public string $scope = 'masuk';

    public ?string $previewHtml = null;
    public ?string $previewUrl = null;
    public ?string $downloadUrl = null;
    public bool $previewModal = false;

    public function getBreadcrumbs(): array
    {
        return match ($this->scope) {
            'keluar' => [
                SuratResource::getUrl('index', ['scope' => 'keluar']) => 'Surat Keluar',
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
        return null;
    }

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
            'suratUnits',
            'disposisis',
            'disposisis.pembuat.jabatanAktif.unitKerja',
            'disposisis.unitTujuan',
        ]);

        $this->suratUnit = \App\Models\SuratUnit::where('surat_id', $this->surat->id)
            ->where('unit_kerja_id', $this->userUnitId)
            ->first();

        if ($this->suratUnit && $this->suratUnit->status_baca === 'BELUM') {
            $this->suratUnit->update([
                'status_baca' => 'SUDAH',
                'tanggal_terima' => now()
            ]);
        }

        // Jika surat masih berstatus TERKIRIM, upgrade menjadi DIPROSES
        if ($this->surat->status_surat === 'TERKIRIM') {
            $this->surat->update(['status_surat' => 'DIPROSES']);

            // Update properti di Livewire agar tombol Setuju/Tolak langsung muncul!
            $this->surat->status_surat = 'DIPROSES';
        }

        $this->jenisTujuanLabel = $this->resolveJenisTujuanLabel();

        if ($this->surat->template_id && $this->surat->template) {
            $service = app(\App\Services\PlaceholderService::class);
            $this->renderedHtml = $service->renderHtml($this->surat->template, $this->surat->content ?? []);
        } else {
            $this->renderedHtml = $this->surat->content['isi_surat'] ?? null;
        }
    }

    protected function getHeaderActions(): array
    {
        $primaryActions = [];
        $secondaryActions = [];

        $user = Auth::user();
        // $activeJabatan = $user->pegawai?->jabatanAktif()->first();
        $unitId = Auth::user()->unit_kerja_id;
        // $unitId = $activeJabatan ? $activeJabatan->unit_kerja_id : null;

        if ($this->surat->status_surat === 'REVISI' && $this->surat->unit_pengirim_id === $unitId) {
            $primaryActions[] = Action::make('edit')
                ->label('Perbaiki Surat')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(\App\Filament\Resources\Surats\Pages\EditSurat::getUrl(['record' => $this->surat]));
        }

        $secondaryActions[] = Action::make('export')
            ->label('Export Surat')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn() => redirect()->route('surat.export', $this->surat));

        // AKSIS KHSUS DRAFT
        if ($this->surat->status_surat !== 'DRAFT') {
            $secondaryActions[] = $this->getActionArsipkan();
        }

        // AKSI KHUSUS SURAT PENGAJUAN
        if ($this->surat->tipe_surat === 'PENGAJUAN') {
            $hasPendingPersetujuan = $this->surat->riwayats()
                ->where('status', 'MENUNGGU')
                ->where('unit_tujuan_id', $unitId)
                ->exists();

            $persetujuan = $this->getActionPersetujuan();

            if ($hasPendingPersetujuan) {
                if (isset($persetujuan[0])) $primaryActions[] = $persetujuan[0]; // Setujui
                if (isset($persetujuan[1])) $secondaryActions[] = $persetujuan[1]; // Minta Revisi
                if (isset($persetujuan[2])) $secondaryActions[] = $persetujuan[2]; // Tolak
            }

            if (isset($persetujuan[3])) $primaryActions[] = $persetujuan[3]; // Buat Terbitan
        }

        // AKSI KHUSUS SURAT INTERNAL
        if ($this->surat->tipe_surat === 'INTERNAL' && $this->surat->unit_pengirim_id !== $unitId) {
            $hasPendingTugas = $this->surat->riwayats()
                ->where('status', 'MENUNGGU')
                ->where('unit_tujuan_id', $unitId)
                ->exists();
            if ($hasPendingTugas) {
                $internalActions = $this->getActionSelesaiInternal();
                if (isset($internalActions[0])) $primaryActions[] = $internalActions[0];
            }
        }

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

    public function openPreview(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        if (str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf') {
            $this->previewUrl = route('media.file', $media->id);
            $this->downloadUrl = route('media.download', $media->id);
        } elseif ($media->getCustomProperty('preview_media_id')) {
            $previewMedia = Media::find($media->getCustomProperty('preview_media_id'));
            $this->previewUrl = route('media.file', $previewMedia->id);
            $this->downloadUrl = route('media.download', $media->id);
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

    protected function updateStatusSurat(): void
    {
        $allDone = $this->surat->disposisis->every(fn($d) => $d->status_disposisi === 'SELESAI');

        $this->surat->update([
            'status_surat' => $allDone ? 'SELESAI' : 'DIPROSES',
        ]);
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
