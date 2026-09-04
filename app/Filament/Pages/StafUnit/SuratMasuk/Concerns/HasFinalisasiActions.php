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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
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

        // 2. Generate Nomor Action
        // Berlaku untuk:
        // a. TERBITAN yang belum bernomor
        // b. Atau INTERNAL / PENGAJUAN / EKSTERNAL yang belum bernomor dan user berasal dari unit pengirim / admin
        $canGenerateNomor = empty($this->surat->nomor_surat) && (
            $this->surat->tipe_surat === 'TERBITAN' ||
            $this->surat->unit_pengirim_id == $unitId ||
            Auth::user()?->tipe_entitas === 'ADMIN'
        );

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

                    // Jika tipe TERBITAN, finalisasikan surat dan generate PDF
                    if ($this->surat->tipe_surat === 'TERBITAN') {
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
                            $fileName = 'Surat_Resmi_' . $safeNomor . '.pdf';

                            $this->surat->addMediaFromString($pdfContent)
                                ->usingFileName($fileName)
                                ->toMediaCollection('dokumen-final');
                        }
                    }

                    $this->refreshPage(
                        'Nomor Surat Berhasil Ditetapkan!',
                        "Nomor surat: {$nomorAkhir}" . ($this->surat->tipe_surat === 'TERBITAN' ? '. Surat kini resmi SELESAI dan siap diunduh.' : '.')
                    );
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