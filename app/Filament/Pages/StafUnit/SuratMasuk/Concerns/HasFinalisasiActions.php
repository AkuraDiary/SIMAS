<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use App\Models\FormatNomorSurat;
use App\Services\NomorSuratService;
use App\Services\PlaceholderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;

trait HasFinalisasiActions
{
    protected function getFinalisasiActions(): array
    {
        $actions = [];
        $unitId = Auth::user()->unit_kerja_id;

        // 1. Download PDF Resmi dari Arsip
        if ($this->surat->tipe_surat === 'TERBITAN') {
            $actions[] = Action::make('download_pdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn() => in_array($this->surat->status_surat, ['SELESAI', 'TERBIT']) && $this->surat->hasMedia('dokumen-final'))
                ->action(function () {
                    $media = $this->surat->getFirstMedia('dokumen-final');
                    if ($media) {
                        return response()->download($media->getPath(), $media->file_name);
                    }
                    Notification::make()->title('File PDF belum tergenerate!')->danger()->send();
                });
        }

        $unitId = Auth::user()->unit_kerja_id;
        $sudahBernomor = filled($this->surat->nomor_surat);
        $isUnitPengirim = $this->surat->unit_pengirim_id == $unitId;
        $isUnitPenerima = $this->surat->suratUnits()->where('unit_kerja_id', $unitId)->exists()
            || $this->surat->disposisis()->where('unit_tujuan_id', $unitId)->exists()
            || $this->surat->riwayats()->where('unit_tujuan_id', $unitId)->exists();
        $isAdmin = Auth::user()?->tipe_entitas === 'ADMIN';
        // Aturan Hak Penomoran:
        // 1. Jika BELUM bernomor: Unit Pengirim, Unit Penerima (Eksternal/Pengajuan), atau Terbitan boleh menomori.
        // 2. Jika SUDAH bernomor: HANYA Unit Pengirim atau Admin yang boleh mengubah/mengoreksi nomor.
        $canGenerateNomor = $sudahBernomor
            ? ($isUnitPengirim || $isAdmin)
            : ($this->surat->tipe_surat === 'TERBITAN' || $isUnitPengirim || $isUnitPenerima || $isAdmin);


        if ($canGenerateNomor) {
            $actions[] = Action::make('generate_nomor')
                ->label('Beri Nomor Surat')
                ->icon('heroicon-o-hashtag')
                ->color('primary')
                ->modalHeading('Penomoran Surat' . ($this->surat->tipe_surat ? " ({$this->surat->tipe_surat})" : ''))
                ->modalDescription('Tetapkan nomor surat resmi, sesuaikan tanggal surat (termasuk backdate), atau lakukan kustomisasi nomor sisipan.')
                ->schema([
                    DatePicker::make('tanggal_surat')
                        ->label('Tanggal Surat')
                        ->default(now())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $this->updateNomorPreview($set, $get);
                        }),

                    Select::make('format_id')
                        ->label('Pilih Format Penomoran')
                        ->options(function () use ($unitId) {
                            return app(NomorSuratService::class)->getAvailableFormats($unitId, $this->surat->tipe_surat);
                        })
                        ->default(function () use ($unitId) {
                            return app(NomorSuratService::class)->resolveFormat($unitId, $this->surat->tipe_surat)?->id;
                        })
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $this->updateNomorPreview($set, $get);
                        }),

                    // Dynamic Custom Tags Inputs
                    Group::make()->schema(function (Get $get) {
                        $formatId = $get('format_id');
                        if (!$formatId) return [];

                        $format = FormatNomorSurat::find($formatId);
                        if (!$format) return [];

                        $customTags = app(NomorSuratService::class)->extractCustomTags($format->format_penomoran);
                        if (empty($customTags)) return [];

                        $inputs = [];
                        foreach ($customTags as $tag) {
                            $cleanLabel = ucwords(str_replace('_', ' ', strtolower($tag)));
                            $inputs[] = TextInput::make("custom_tags.{$tag}")
                                ->label("Atribut Format: {$cleanLabel}")
                                ->placeholder("Nilai untuk {{$tag}}")
                                ->helperText("Menggantikan token {{$tag}} pada format: {$format->format_penomoran}")
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    $this->updateNomorPreview($set, $get);
                                });
                        }

                        return $inputs;
                    }),

                    Toggle::make('is_manual')
                        ->label('Kustomisasi Nomor / Sisipan Manual')
                        ->live()
                        ->helperText('Aktifkan jika Anda perlu menyisipkan nomor backdate manual (cth: 045.A) atau menyesuaikan teks nomor surat.'),

                    TextInput::make('nomor_part')
                        ->label('Nomor / Sisipan')
                        ->placeholder('Contoh: 045.A atau 12.B')
                        ->helperText('Nilai ini akan menyubstitusi {NOMOR} pada template.')
                        ->visible(fn(Get $get) => (bool) $get('is_manual'))
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $this->updateNomorPreview($set, $get);
                        }),

                    Checkbox::make('increment_counter')
                        ->label('Naikkan counter nomor urut?')
                        ->default(false)
                        ->visible(fn(Get $get) => (bool) $get('is_manual'))
                        ->helperText('Biarkan tidak dicentang agar nomor sisipan lampau tidak memajukan counter penomoran berjalan.'),

                    TextInput::make('nomor_surat_preview')
                        ->label('Nomor Surat Final')
                        ->disabled(fn(Get $get) => ! $get('is_manual'))
                        ->dehydrated(true)
                        ->required()
                        ->helperText(fn(Get $get) => $get('is_manual')
                            ? 'Anda dapat mengedit bebas teks nomor surat final ini jika diperlukan.'
                            : 'Dihasilkan otomatis sesuai template dan nomor urut.'),

                    Textarea::make('alasan_backdate')
                        ->label('Alasan Backdate')
                        ->placeholder('Contoh: Surat keputusan telah ditetapkan pada tanggal lampau dan baru diadministrasikan hari ini.')
                        ->helperText('Wajib diisi karena tanggal surat mendahului tanggal hari ini (audit trail).')
                        ->visible(fn(Get $get) => app(NomorSuratService::class)->isDateBackdate($get('tanggal_surat')))
                        ->required(fn(Get $get) => app(NomorSuratService::class)->isDateBackdate($get('tanggal_surat'))),
                ])
                ->mountUsing(function ($form) use ($unitId) {
                    $service = app(NomorSuratService::class);
                    $format = $service->resolveFormat($unitId, $this->surat->tipe_surat);
                    $initialPreview = '';
                    $savedTags = $this->surat->content['nomor_surat_tags'] ?? [];

                    if ($format) {
                        $initialPreview = $service->previewNomor(
                            $format,
                            now(),
                            null,
                            $this->surat->unitPengirim ?? Auth::user()->unitKerja,
                            $this->surat->tipe_surat,
                            array_merge(
                                $savedTags,
                                $this->surat->content ?? []
                            )
                        );
                    }

                    $form->fill([
                        'tanggal_surat' => now()->toDateString(),
                        'format_id' => $format?->id,
                        'is_manual' => false,
                        'increment_counter' => false,
                        'custom_tags' => $savedTags,
                        'nomor_surat_preview' => $initialPreview,
                    ]);
                })
                ->action(function (array $data) {
                    $format = FormatNomorSurat::find($data['format_id']);
                    if (!$format) {
                        Notification::make()->title('Format nomor tidak ditemukan')->danger()->send();
                        return;
                    }

                    $service = app(NomorSuratService::class);
                    $nomorAkhir = $service->assignNomorSurat($this->surat, $format, [
                        'tanggal_surat' => $data['tanggal_surat'],
                        'nomor_surat_preview' => $data['nomor_surat_preview'],
                        'is_manual' => (bool) ($data['is_manual'] ?? false),
                        'increment_counter' => $data['is_manual']
                            ? (bool) ($data['increment_counter'] ?? false)
                            : true,
                        'alasan_backdate' => $data['alasan_backdate'] ?? null,
                        'custom_tags' => $data['custom_tags'] ?? [],
                        'user_id' => Auth::id(),
                    ]);

                    $hasPendingSteps = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->exists();

                    // Jika semua langkah persetujuan telah tuntas, finalisasikan surat dan terbitkan dokumen
                    if (!$hasPendingSteps) {
                        $this->surat->status_surat = 'SELESAI';
                        $this->surat->save();

                        if ($this->surat->template_id) {
                            $html = app(PlaceholderService::class)->renderHtml(
                                $this->surat->template,
                                $this->surat->content ?? [],
                                $this->surat
                            );

                            $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
                            $pdfContent = $pdf->output();

                            $safeNomor = str_replace(['/', '\\'], '_', $nomorAkhir);
                            $fileName = 'Surat_Utama_' . $safeNomor . '.pdf';

                            $this->surat->addMediaFromString($pdfContent)
                                ->usingFileName($fileName)
                                ->toMediaCollection('dokumen-final');
                        }

                        // Jika surat ini rujukan atas pengajuan pemohon, selesaikan pengajuan & notifikasi pemohon
                        if ($this->surat->terbitan_for_surat_id) {
                            $pengajuan = \App\Models\Surat::find($this->surat->terbitan_for_surat_id);
                            if ($pengajuan) {
                                $pengajuan->update(['status_surat' => 'SELESAI']);

                                if ($pengajuan->user_pembuat_id) {
                                    $targetUser = \App\Models\User::find($pengajuan->user_pembuat_id);
                                    if ($targetUser) {
                                        Notification::make()
                                            ->title('Surat Terbitan Selesai')
                                            ->body('Pengajuan Anda telah diproses dan Surat Balasan/Rekomendasi telah diterbitkan.')
                                            ->success()
                                            ->viewData([
                                                'unit_kerja_id' => (int) ($pengajuan->unit_pengirim_id ?? $this->surat->unit_pengirim_id),
                                                'surat_id'      => $this->surat->id,
                                            ])
                                            ->sendToDatabase($targetUser);

                                        app(\App\Services\WhatsAppNotificationService::class)->notifySuratSelesai(
                                            $this->surat,
                                            $targetUser,
                                            'Pengajuan Anda telah diproses dan Surat Balasan/Rekomendasi telah diterbitkan.'
                                        );
                                    }
                                }
                            }
                        }
                    }


                    $pesanSukses = "Nomor surat {$nomorAkhir} berhasil ditetapkan.";
                    if ($this->surat->status_surat === 'SELESAI') {
                        $pesanSukses .= ' Surat kini resmi SELESAI dan diterbitkan.';
                    } else {
                        $pesanSukses .= ' Nomor tercatat pada draf, alur persetujuan berlanjut ke tahap berikutnya.';
                    }
                    $this->refreshPage('Nomor Surat Ditetapkan!', $pesanSukses);
                });
        }

        return $actions;
    }

    protected function updateNomorPreview(Set $set, Get $get): void
    {
        $formatId = $get('format_id');
        if (!$formatId) return;

        $format = FormatNomorSurat::find($formatId);
        if (!$format) return;

        $service = app(NomorSuratService::class);
        $tgl = $get('tanggal_surat') ? Carbon::parse($get('tanggal_surat')) : Carbon::now();
        $isManual = (bool) $get('is_manual');
        $customPart = $isManual ? $get('nomor_part') : null;

        $customTags = array_merge(
            $this->surat->content['nomor_surat_tags'] ?? [],
            $this->surat->content ?? [],
            $get('custom_tags') ?? []
        );

        $preview = $service->previewNomor(
            $format,
            $tgl,
            $customPart,
            $this->surat->unitPengirim ?? Auth::user()->unitKerja,
            $this->surat->tipe_surat,
            $customTags
        );

        $set('nomor_surat_preview', $preview);
    }
}
