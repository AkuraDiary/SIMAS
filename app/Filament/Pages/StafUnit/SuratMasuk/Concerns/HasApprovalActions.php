<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

trait HasApprovalActions
{
    /**
     * Mendeteksi konteks langkah approval path untuk user yang sedang login.
     */

    protected function getApprovalPathInfo(): array
    {
        $path = $this->surat->approval_path;
        if (empty($path) || !is_array($path) || count($path) === 0) {
            return [
                'has_path'           => false,
                'is_intermediate'    => false,
                'is_final'           => false,
                'current_step'       => null,
                'next_step'          => null,
                'next_jabatan_name'  => null,
                'requires_signature' => true,
            ];
        }

        $totalSteps = count($path);

        // 1. Kumpulkan semua ID jabatan yang dipegang user saat ini
        $userJabatanIds = [];
        if ($activeJabatan = Auth::user()?->getActiveJabatan()) {
            $userJabatanIds[] = (int) $activeJabatan->jabatan_id;
        }
        if ($pegawai = Auth::user()?->pegawai) {
            $userJabatanIds = array_merge(
                $userJabatanIds,
                $pegawai->jabatanAktif()->pluck('jabatan_id')->map(fn($id) => (int) $id)->toArray()
            );
        }
        $userJabatanIds = array_unique(array_filter($userJabatanIds));

        // 2. Cari index langkah di mana jabatan user berada
        $currentIndex = -1;
        foreach ($path as $index => $step) {
            $stepJabatanId = (int) ($step['jabatan_id'] ?? 0);
            if (in_array($stepJabatanId, $userJabatanIds, true)) {
                $currentIndex = $index;
                break;
            }
        }

        // 3. Fallback cerdas: Jika ID jabatan tidak match persis, hitung berapa langkah yang sudah disetujui
        if ($currentIndex === -1) {
            $approvedCount = $this->surat->riwayats()->where('status', 'DISETUJUI')->count();
            $currentIndex = min($approvedCount, $totalSteps - 1);
        }

        $currentStep = $path[$currentIndex] ?? null;
        $isIntermediate = ($currentIndex < $totalSteps - 1);
        $isFinal = !$isIntermediate;
        $nextStep = $isIntermediate ? ($path[$currentIndex + 1] ?? null) : null;
        $nextJabatanName = $nextStep ? (\App\Models\Jabatan::find($nextStep['jabatan_id'])?->nama_jabatan ?? 'Tahap Selanjutnya') : null;

        return [
            'has_path'           => true,
            'is_intermediate'    => $isIntermediate,
            'is_final'           => $isFinal,
            'current_step'       => $currentStep,
            'next_step'          => $nextStep,
            'next_jabatan_name'  => $nextJabatanName,
            'requires_signature' => (bool) ($currentStep['is_signer'] ?? true),
        ];
    }

    public function resolveMyPlaceholderKey(): string
    {
        $actor = Auth::user();
        if (!$actor) {
            return 'ttd_approver';
        }

        $existing = $this->surat->suratTtds->firstWhere('user_id', $actor->id);
        if ($existing && $existing->placeholder_key) {
            return $existing->placeholder_key;
        }

        $info = $this->getApprovalPathInfo();
        if (!empty($info['current_step']['placeholder_key'])) {
            return $info['current_step']['placeholder_key'];
        }

        return 'ttd_user_' . $actor->id;
    }

    public function getMySuratTtd(): ?\App\Models\SuratTtd
    {
        $actor = Auth::user();
        if (!$actor) {
            return null;
        }
        return $this->surat->suratTtds->firstWhere('user_id', $actor->id);
    }

    public function isMyStepPending(): bool
    {
        $unitId = Auth::user()?->unit_kerja_id;
        if (!$unitId || !in_array($this->surat->status_surat, ['DIPROSES', 'TERKIRIM'], true)) {
            return false;
        }
        return $this->surat->riwayats
            ->where('status', 'MENUNGGU')
            ->where('unit_tujuan_id', $unitId)
            ->isNotEmpty();
    }

    /**
     * Toggle on-the-fly: Tampilkan / Sembunyikan Nama & Jabatan di bawah QR/TTD (Hanya saat tahap Tweaking / Pra-Finalisasi)
     */
    public function toggleMyTtdShowName(): void
    {
        if (!$this->isMyStepPending()) {
            Notification::make()->title('Surat sudah difinalisasi, format nama tidak dapat diubah lagi.')->warning()->send();
            return;
        }

        $ttd = $this->getMySuratTtd();
        if (!$ttd) {
            return;
        }

        $key = $ttd->placeholder_key ?: $this->resolveMyPlaceholderKey();
        $content = $this->surat->content ?? [];
        $current = (bool) ($content[$key . '_show_name'] ?? true);
        $content[$key . '_show_name'] = !$current;

        $this->surat->update(['content' => $content]);
        $this->surat->refresh();

        if ($this->surat->template_id && $this->surat->template) {
            $this->renderedHtml = app(\App\Services\PlaceholderService::class)
                ->renderHtml($this->surat->template, $this->surat->content ?? [], $this->surat);
        }
    }

    /**
     * Toggle on-the-fly: Tampilkan / Sembunyikan seluruh blok TTD/QR (Tersedia saat Tweaking maupun Pasca-Finalisasi)
     */
    public function toggleMyTtdVisibility(): void
    {
        $ttd = $this->getMySuratTtd();
        if (!$ttd) {
            return;
        }

        $ttd->update(['is_visible' => !$ttd->is_visible]);
        $this->surat->refresh();

        if ($this->surat->template_id && $this->surat->template) {
            $this->renderedHtml = app(\App\Services\PlaceholderService::class)
                ->renderHtml($this->surat->template, $this->surat->content ?? [], $this->surat);
        }

        if ($this->surat->status_surat === 'SELESAI') {
            app(\App\Services\SuratExportService::class)->generateAndAttachDokumenFinal($this->surat);
        }
    }

    /**
     * Standalone Action yang dapat dipanggil dari tombol di atas kertas surat ($wire.mountAction('setupSignature'))
     */
    public function setupSignatureAction(): Action
    {
        return Action::make('setupSignature')
            ->label(fn() => $this->getMySuratTtd() ? 'Ubah Metode / Gambar TTD' : 'Bubuhkan & Atur TTD Pengesahan')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->modalHeading('Pengaturan Tanda Tangan & QR Pengesahan')
            ->modalDescription('Pilih metode tanda tangan/QR Anda. Setelah disimpan, Anda dapat menggeser posisi dan mengatur tampilan nama secara langsung di lembar surat sebelum melakukan Finalisasi.')
            ->modalSubmitActionLabel('Terapkan ke Lembar Surat')
            ->visible(fn() => $this->isMyStepPending())
            ->fillForm(function (): array {
                $ttd = $this->getMySuratTtd();
                $key = $this->resolveMyPlaceholderKey();
                $content = $this->surat->content ?? [];

                return [
                    'is_visible'         => $ttd ? (bool) $ttd->is_visible : true,
                    'qr_code_type'       => $content[$key . '_mode'] ?? 'generate',
                    'tampilkan_nama_ttd' => array_key_exists($key . '_show_name', $content) ? (bool) $content[$key . '_show_name'] : true,
                ];
            })
            ->schema($this->getSignatureFieldsetSchema())
            ->action(function (array $data): void {
                if (!$this->isMyStepPending()) {
                    return;
                }

                $info = $this->getApprovalPathInfo();
                $placeholderKey = $this->resolveMyPlaceholderKey();

                app(\App\Services\SignatureService::class)->processDigitalSignature(
                    $this->surat,
                    Auth::user(),
                    $data,
                    $placeholderKey,
                    'UTAMA'
                );

                $this->refreshPage(
                    'TTD Diterapkan ke Surat',
                    'Silakan sesuaikan posisi atau toggle nama langsung di lembar surat, lalu klik Finalisasi jika sudah sesuai.'
                );
            });
    }

    protected function getSignatureFieldsetSchema(): array
    {
        return [
            Toggle::make('is_visible')
                ->label('Tampilkan TTD / QR Pengesahan pada Lembar Surat')
                ->helperText('Jika dinonaktifkan, persetujuan tetap tercatat di Lembar Kendali namun tidak ditempelkan di halaman surat.')
                ->default(true)
                ->live()
                ->columnSpanFull(),

            Radio::make('qr_code_type')
                ->label('Format Visual Pengesahan')
                ->options([
                    'generate' => 'QR Code Verifikasi Sistem (Otomatis)',
                    'upload'   => 'Unggah Gambar QR Code / Stempel TTD Eksternal',
                    'draw'     => 'Goreskan Tanda Tangan Langsung',
                    'both'     => 'Kombinasi: Goresan TTD + QR Verifikasi Sistem',
                    'none'     => 'Teks Nama & Jabatan Saja (Tanpa Gambar)',
                ])
                ->default('generate')
                ->visible(fn(Get $get) => (bool) $get('is_visible'))
                ->live()
                ->columnSpanFull(),

            Toggle::make('tampilkan_nama_ttd')
                ->label('Tampilkan Nama & Jabatan Penandatangan di Bawah QR / TTD')
                ->helperText('Matikan opsi ini jika Anda hanya ingin menampilkan gambar QR Code / TTD saja.')
                ->default(true)
                ->live()
                ->visible(fn(Get $get) => (bool) $get('is_visible') && $get('qr_code_type') !== 'none')
                ->columnSpanFull(),

            FileUpload::make('custom_qr_code')
                ->label('File Gambar QR Code / TTD')
                ->image()
                ->disk('private')
                ->directory('signatures')
                ->live()
                ->visible(fn(Get $get) => (bool) $get('is_visible') && $get('qr_code_type') === 'upload')
                ->required(fn(Get $get) => (bool) $get('is_visible') && $get('qr_code_type') === 'upload')
                ->columnSpanFull(),

            SignaturePad::make('drawn_signature')
                ->label('Gambar Tanda Tangan')
                ->downloadable()
                ->downloadableFormats([
                    DownloadableFormat::PNG,
                    DownloadableFormat::JPG,
                    DownloadableFormat::SVG,
                ])
                ->exportBackgroundColor('rgba(0,0,0,0)')
                ->exportPenColor('#000000')
                ->backgroundColor('#ffffff')
                ->backgroundColorOnDark('#111111')
                ->penColor('#000000')
                ->penColorOnDark('#ffffff')
                ->live()
                ->visible(fn(Get $get) => (bool) $get('is_visible') && in_array($get('qr_code_type'), ['draw', 'both'], true))
                ->required(fn(Get $get) => (bool) $get('is_visible') && in_array($get('qr_code_type'), ['draw', 'both'], true))
                ->columnSpanFull(),
        ];
    }

    protected function getActionPersetujuan(): array
    {
        return [
            'group_proses' => \Filament\Actions\ActionGroup::make([
                // 0. TAHAP 2: BUBUHKAN / UBAH TTD PENGESAHAN (Tweak On-The-Fly sebelum Finalisasi)
                Action::make('setup_signature_menu')
                    ->label(fn() => $this->getMySuratTtd() ? '1. Ubah Metode / Gambar TTD' : '1. Bubuhkan & Atur TTD di Surat')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->modalHeading('Pengaturan Tanda Tangan & QR Pengesahan')
                    ->modalDescription('Pilih metode tanda tangan/QR Anda. Setelah disimpan, Anda dapat menggeser posisi dan mengatur tampilan nama secara langsung di lembar surat sebelum melakukan Finalisasi.')
                    ->modalSubmitActionLabel('Terapkan ke Lembar Surat')
                    ->fillForm(function (): array {
                        $ttd = $this->getMySuratTtd();
                        $key = $this->resolveMyPlaceholderKey();
                        $content = $this->surat->content ?? [];

                        return [
                            'is_visible'         => $ttd ? (bool) $ttd->is_visible : true,
                            'qr_code_type'       => $content[$key . '_mode'] ?? 'generate',
                            'tampilkan_nama_ttd' => array_key_exists($key . '_show_name', $content) ? (bool) $content[$key . '_show_name'] : true,
                        ];
                    })
                    ->schema($this->getSignatureFieldsetSchema())
                    ->action(function (array $data): void {
                        $placeholderKey = $this->resolveMyPlaceholderKey();

                        app(\App\Services\SignatureService::class)->processDigitalSignature(
                            $this->surat,
                            Auth::user(),
                            $data,
                            $placeholderKey,
                            'UTAMA'
                        );

                        $this->refreshPage(
                            'TTD Diterapkan ke Surat',
                            'Silakan sesuaikan posisi atau toggle nama langsung di lembar surat, lalu klik Finalisasi jika sudah sesuai.'
                        );
                    }),

                // 1. TAHAP 3 (FINAL): FINALISASI & SELESAIKAN SURAT
                Action::make('approve_finish')
                    ->label(function () {
                        $info = $this->getApprovalPathInfo();
                        $prefix = $this->getMySuratTtd() ? '2. Finalisasi & Selesaikan Surat' : 'Finalisasi & Selesai';
                        return ($info['has_path'] && $info['is_final']) ? $prefix : 'Finalisasi & Selesai';
                    })
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function () {
                        $info = $this->getApprovalPathInfo();
                        return !($info['has_path'] && $info['is_intermediate']);
                    })
                    ->schema(function () {
                        $alreadyHasTtd = (bool) $this->getMySuratTtd();
                        $schema = [
                            Textarea::make('catatan')
                                ->label('Catatan Persetujuan Akhir (Opsional)'),
                        ];

                        if (!$alreadyHasTtd) {
                            $schema[] = Fieldset::make('Tanda Tangan Digital (Belum Diatur di Surat)')
                                ->schema($this->getSignatureFieldsetSchema());
                        } else {
                            $schema[] = TextEntry::make('info_ttd_siap')
                                ->label('Status Tanda Tangan')
                                ->state('Tanda tangan / QR Anda sudah terpasang di lembar surat. Setelah difinalisasi, Anda hanya dapat menggeser posisi atau menyembunyikan/menampilkan TTD secara utuh.');
                        }

                        return $schema;
                    })
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->approveStep(
                            $activeRiwayat,
                            Auth::user(),
                            null,
                            null,
                            true,
                            true,
                            'UTAMA',
                            $data['catatan'] ?? null,
                            $data
                        );
                        $this->refreshPage('Berhasil Difinalisasi', 'Surat resmi disetujui dan difinalisasi.');
                    }),

                // 2. TAHAP 3 (INTERMEDIATE): FINALISASI & LANJUTKAN KE TAHAP BERIKUTNYA
                Action::make('approve_forward')
                    ->label(function () {
                        $info = $this->getApprovalPathInfo();
                        if ($info['has_path'] && $info['is_intermediate']) {
                            return '2. Finalisasi & Lanjutkan ke ' . ($info['next_jabatan_name'] ?? 'Tahap Berikutnya');
                        }
                        return 'Finalisasi & Teruskan';
                    })
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(function () {
                        $info = $this->getApprovalPathInfo();
                        return !($info['has_path'] && $info['is_final']);
                    })
                    ->schema(function () {
                        $info = $this->getApprovalPathInfo();
                        $isIntermediate = $info['has_path'] && $info['is_intermediate'];
                        $alreadyHasTtd = (bool) $this->getMySuratTtd();

                        $schema = [
                            TextEntry::make('info_tujuan_otomatis')
                                ->label('Tujuan Selanjutnya (Otomatis)')
                                ->state(fn() => 'Surat akan otomatis diteruskan ke ' . ($info['next_jabatan_name'] ?? 'tahap berikutnya') . ' sesuai alur persetujuan.')
                                ->visible($isIntermediate),

                            Select::make('next_unit_tujuan_id')
                                ->label('Teruskan Ke Unit')
                                ->options(fn() => \App\Models\UnitKerja::where('id', '!=', Auth::user()->unit_kerja_id)->pluck('nama_unit', 'id'))
                                ->searchable()
                                ->visible(!$isIntermediate)
                                ->required(!$isIntermediate),
                        ];

                        if (!$alreadyHasTtd) {
                            $schema[] = Toggle::make('tambah_ttd')
                                ->label('Tambahkan Tanda Tangan / Paraf')
                                ->default($info['requires_signature'])
                                ->reactive();

                            $schema[] = Fieldset::make('Tanda Tangan Digital')
                                ->schema($this->getSignatureFieldsetSchema())
                                ->visible(fn(Get $get) => (bool) $get('tambah_ttd'));
                        } else {
                            $schema[] = TextEntry::make('info_ttd_siap')
                                ->label('Status Tanda Tangan')
                                ->state('Tanda tangan / QR Anda sudah terpasang di lembar surat. Setelah diteruskan, Anda hanya dapat menggeser posisi atau menyembunyikan/menampilkan TTD secara utuh.');
                        }

                        $schema[] = Textarea::make('catatan')->label('Catatan Penerusan (Opsional)');

                        return $schema;
                    })
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        $info = $this->getApprovalPathInfo();
                        $alreadyHasTtd = (bool) $this->getMySuratTtd();
                        $needsSig = $alreadyHasTtd ? true : (bool) ($data['tambah_ttd'] ?? $info['requires_signature']);

                        app(\App\Services\SuratRoutingService::class)->approveStep(
                            $activeRiwayat,
                            Auth::user(),
                            $data['next_unit_tujuan_id'] ?? null,
                            null,
                            false,
                            $needsSig,
                            'UTAMA',
                            $data['catatan'] ?? null,
                            $data
                        );
                        $this->refreshPage('Berhasil Difinalisasi', 'Surat disetujui dan diteruskan ke tahap berikutnya.');
                    }),

                // 3. TERUSKAN SAJA (Disposisi manual tanpa persetujuan formal)
                Action::make('pure_forward')
                    ->label('Teruskan Saja (Tanpa Setuju)')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('gray')
                    ->schema([
                        Select::make('next_unit_tujuan_id')
                            ->label('Teruskan Ke Unit')
                            ->options(fn() => \App\Models\UnitKerja::where('id', '!=', Auth::user()->unit_kerja_id)->pluck('nama_unit', 'id'))
                            ->searchable()->required(),
                        Textarea::make('catatan')->label('Catatan (Opsional)'),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->forwardStep(
                            $activeRiwayat,
                            Auth::user(),
                            $data['next_unit_tujuan_id'],
                            $data['catatan'] ?? null
                        );
                        $this->refreshPage('Berhasil', 'Surat diteruskan tanpa persetujuan.');
                    }),
            ])
                ->label('Proses Surat')
                ->icon('heroicon-m-check-circle')
                ->button()
                ->color('success')
                ->visible(fn() => in_array($this->surat->status_surat, ['DIPROSES', 'TERKIRIM'])),

            'group_kembalikan' => \Filament\Actions\ActionGroup::make([
                Action::make('step_back')
                    ->label('Kembalikan ke Langkah Sebelumnya')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Dikembalikan')->required(),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->returnStep($activeRiwayat, Auth::user(), $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat dikembalikan ke unit sebelumnya.');
                    }),

                Action::make('reject')
                    ->label('Kembalikan ke Pembuat Awal (Reset)')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Revisi Total')->required(),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep($activeRiwayat, Auth::user(), 'REVISI', $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat dikembalikan secara total ke pembuat.');
                    }),

                Action::make('tolak_persetujuan')
                    ->label('Tolak Surat Sepenuhnya')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Penolakan')->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep($activeRiwayat, Auth::user(), 'DITOLAK', $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat pengajuan ditolak permanen.');
                    }),
            ])
                ->label('Kembalikan / Tolak')
                ->icon('heroicon-m-x-circle')
                ->button()
                ->color('danger')
                ->outlined()
                ->visible(fn() => in_array($this->surat->status_surat, ['DIPROSES', 'TERKIRIM'])),

            'terbitan' => Action::make('buat_terbitan')
                ->label('Terbitkan Surat Balasan')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->visible(
                    fn() => $this->surat->tipe_surat === 'PENGAJUAN' &&
                        in_array($this->surat->status_surat, ['DIPROSES', 'SELESAI']) &&
                        Auth::user()->unit_kerja_id !== $this->surat->unit_pengirim_id
                )
                ->url(fn() => \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl(['terbitan_for_surat_id' => $this->surat->id, 'tipe_surat' => 'TERBITAN']))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get the currently active routing step (Riwayat) for the logged-in user's unit.
     */
    protected function getActiveRiwayat()
    {
        return $this->surat->riwayats()
            ->where('status', 'MENUNGGU')
            ->where('unit_tujuan_id', \Illuminate\Support\Facades\Auth::user()->unit_kerja_id)
            ->latest()
            ->first();
    }
}
