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

    protected function renderModalLivePreview(Get $get): \Illuminate\Support\HtmlString
    {
        if (!$this->surat->template_id || !$this->surat->template) {
            return new \Illuminate\Support\HtmlString('');
        }

        $info = $this->getApprovalPathInfo();
        $stepKey = $info['current_step']['placeholder_key'] ?? ('ttd_user_' . Auth::id());
        $isVisible = (bool) ($get('is_visible') ?? true);
        $qrType = $get('qr_code_type') ?? 'generate';
        $showName = (bool) ($get('tampilkan_nama_ttd') ?? true);
        $drawn = $get('drawn_signature');

        $data = $this->surat->content ?? [];
        $actor = Auth::user();
        $jabatanName = $actor?->getActiveJabatan()?->jabatan?->nama_jabatan ?? 'Pejabat Berwenang';
        $namaTerang = $actor?->nama_lengkap ?? 'Pejabat Berwenang';

        $widthVal = (int) ($data[$stepKey . '_width'] ?? 160);
        $imgSize = max(60, min(220, $widthVal - 20));
        $x = (int) ($data[$stepKey . '_posisi_x'] ?? 0);
        $y = (int) ($data[$stepKey . '_posisi_y'] ?? 0);

        if (!$isVisible) {
            $data[$stepKey] = '';
        } else {
            $imgHtml = '';
            if (in_array($qrType, ['generate', 'both'], true)) {
                $imgHtml .= '<img src="' . asset('images/qr_placeholder.png') . '" style="width: 100%; max-width: ' . $imgSize . 'px; height: auto; margin-bottom: 4px; display: block;" pointer-events="none" />';
            }
            if (in_array($qrType, ['draw', 'both'], true) && !empty($drawn)) {
                $imgHtml .= '<img src="' . htmlspecialchars($drawn) . '" style="width: 100%; max-width: ' . $imgSize . 'px; height: auto; margin-bottom: 4px; display: block;" pointer-events="none" />';
            }
            $nameHtml = $showName
                ? ('<b><u>' . htmlspecialchars($namaTerang) . '</u></b><br><span style="font-size: 10pt;">' . htmlspecialchars($jabatanName) . '</span>')
                : '';

            $style = "text-align: left; display: inline-block; cursor: grab; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";
            $resizeHandle = '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>';
            $visual = '<div class="draggable-signature" data-key="' . $stepKey . '" style="' . $style . '">' . $imgHtml . $nameHtml . $resizeHandle . '</div>';

            $data[$stepKey] = $visual;
            $data['__live_approver_preview_' . $stepKey] = $imgHtml . $nameHtml;
        }

        $html = app(\App\Services\PlaceholderService::class)->renderHtml($this->surat->template, $data, $this->surat);

        return new \Illuminate\Support\HtmlString(
            view('filament.forms.components.template-preview', [
                'html' => $html,
                'isDetailPage' => true,
            ])->render()
        );
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

            TextEntry::make('live_ttd_preview')
                ->label('Pratinjau & Penataan Posisi TTD / QR pada Surat (Geser / Ubah Ukuran Langsung)')
                ->state(fn(Get $get) => $this->renderModalLivePreview($get))
                ->html()
                ->visible(fn() => (bool) $this->surat->template_id)
                ->columnSpanFull(),
        ];
    }
    protected function getActionPersetujuan(): array
    {
        return [
            'group_proses' => \Filament\Actions\ActionGroup::make([
                // 1. SETUJUI & SELESAI (Hanya untuk langkah final atau surat tanpa path)
                Action::make('approve_finish')
                    ->label(function () {
                        $info = $this->getApprovalPathInfo();
                        return ($info['has_path'] && $info['is_final'])
                            ? 'Setujui & Selesaikan (Tanda Tangan Final)'
                            : 'Setujui & Selesai';
                    })
                    ->modalWidth('5xl')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function () {
                        $info = $this->getApprovalPathInfo();
                        // Sembunyikan jika masih ada langkah berikutnya di approval_path
                        return !($info['has_path'] && $info['is_intermediate']);
                    })
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan Persetujuan Akhir (Opsional)'),

                        Fieldset::make('Tanda Tangan Digital')
                            ->schema($this->getSignatureFieldsetSchema()),
                    ])
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
                        $this->refreshPage('Berhasil', 'Surat berhasil disetujui & ditandatangani.');
                    }),

                // 2. SETUJUI & TERUSKAN / LANJUTKAN (Untuk langkah perantara atau alur bebas)
                Action::make('approve_forward')
                    ->label(function () {
                        $info = $this->getApprovalPathInfo();
                        if ($info['has_path'] && $info['is_intermediate']) {
                            return 'Verifikasi & Lanjutkan ke ' . ($info['next_jabatan_name'] ?? 'Tahap Berikutnya');
                        }
                        return 'Setujui & Teruskan';
                    })
                    ->modalWidth('5xl')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(function () {
                        $info = $this->getApprovalPathInfo();
                        // Sembunyikan jika sudah berada di langkah final
                        return !($info['has_path'] && $info['is_final']);
                    })
                    ->schema(function () {
                        $info = $this->getApprovalPathInfo();
                        $isIntermediate = $info['has_path'] && $info['is_intermediate'];

                        return [
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

                            Toggle::make('tambah_ttd')
                                ->label('Tambahkan Tanda Tangan / Paraf')
                                ->default($info['requires_signature'])
                                ->reactive(),

                            Fieldset::make('Tanda Tangan Digital')
                                ->schema($this->getSignatureFieldsetSchema())
                                ->visible(fn(Get $get) => (bool) $get('tambah_ttd')),

                            Textarea::make('catatan')->label('Catatan Penerusan (Opsional)'),
                        ];
                    })
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->getActiveRiwayat();
                        if (!$activeRiwayat) return;

                        $info = $this->getApprovalPathInfo();
                        $needsSig = (bool) ($data['tambah_ttd'] ?? $info['requires_signature']);

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
                        $this->refreshPage('Berhasil', 'Surat disetujui dan diteruskan ke tahap berikutnya.');
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
