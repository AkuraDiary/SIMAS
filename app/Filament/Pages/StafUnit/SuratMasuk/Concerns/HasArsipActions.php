<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use App\Models\ArsipSurat;
use App\Models\KategoriArsip;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

trait HasArsipActions
{
    protected function getActionArsipkan(): Action
    {
        return Action::make('arsipkan')
            ->label('Arsipkan')
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->visible(fn() => ! $this->sudahDiarsipkan())
            ->modalHeading('Arsipkan Surat')
            ->modalDescription('Pilih kategori arsip untuk menyimpan surat ini ke dalam arsip unit kerja.')
            ->schema([
                Select::make('kategori_arsip_id')
                    ->label('Kategori Arsip')
                    ->options(
                        fn() => KategoriArsip::where('unit_kerja_id', Auth::user()->unit_kerja_id)
                            ->pluck('nama', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
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
                            'nama' => trim($data['nama']),
                        ])->id;
                    }),

                Textarea::make('catatan')
                    ->label('Catatan Arsip (Opsional)')
                    ->placeholder('Misal: Disimpan untuk referensi akreditasi')
                    ->rows(3),
            ])
            ->action(fn($data) => $this->handleArsipkanSurat($data));
    }

    protected function getActionArsipInfo(): Action
    {
        return Action::make('info_arsip')
            ->label(function () {
                $arsip = $this->getArsipSurat();
                $kategori = $arsip?->kategoriArsip?->nama;
                return $kategori ? "Diarsipkan: {$kategori}" : 'Sudah Diarsipkan';
            })
            ->icon('heroicon-s-archive-box')
            ->color('gray')
            ->modalHeading('Informasi Arsip Surat')
            ->modalDescription('Surat ini telah diarsipkan oleh unit kerja Anda.')
            ->modalContent(function () {
                $arsip = $this->getArsipSurat();
                return view('filament.pages.staf-unit.surat-masuk.info-arsip-modal', [
                    'arsip' => $arsip,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->visible(fn() => $this->sudahDiarsipkan());
    }

    protected function handleArsipkanSurat(array $data): void
    {
        $unitId = Auth::user()->unit_kerja_id;

        ArsipSurat::updateOrCreate(
            [
                'surat_id' => $this->surat->id,
                'unit_kerja_id' => $unitId,
            ],
            [
                'kategori_arsip_id' => $data['kategori_arsip_id'],
                'tanggal_arsip' => now(),
                'catatan' => $data['catatan'] ?? null,
            ]
        );

        $this->refreshPage('Surat Diarsipkan', 'Surat berhasil masuk arsip unit.');
    }

    protected function getArsipSurat(): ?ArsipSurat
    {
        $unitId = Auth::user()?->unit_kerja_id;
        if (!$unitId || !$this->surat?->id) {
            return null;
        }

        return ArsipSurat::where('surat_id', $this->surat->id)
            ->where('unit_kerja_id', $unitId)
            ->with('kategoriArsip')
            ->first();
    }

    protected function sudahDiarsipkan(): bool
    {
        return $this->getArsipSurat() !== null;
    }
}
