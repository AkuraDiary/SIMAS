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

    protected function sudahDiarsipkan(): bool
    {
        return ArsipSurat::where('surat_id', $this->surat->id)
            ->where('unit_kerja_id', Auth::user()->unit_kerja_id)
            ->exists();
    }
}
