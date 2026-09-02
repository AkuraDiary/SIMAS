<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use App\Models\Disposisi;
use App\Models\UnitKerja;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait HasDisposisiActions
{
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
            \Filament\Forms\Components\Repeater::make('tujuan_disposisi')
                ->label('Daftar Tujuan & Instruksi')
                ->schema([
                    Select::make('unit_tujuan_id')
                        ->label('Unit Tujuan')
                        ->options(
                            UnitKerja::query()->where('id', '<>', Auth::user()->unit_kerja_id)
                                ->pluck('nama_unit', 'id')
                        )
                        ->searchable()
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
                        ->rows(2)
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
                        ->label('Catatan (Opsional)')
                        ->rows(2),
                ])
                ->columns(2) // Makes the repeater look compact
                ->minItems(1)
                ->addActionLabel('Tambah Tujuan Disposisi'),

            // Bukti ditaruh di luar repeater agar cukup diupload 1 kali untuk seluruh disposisi ini
            SpatieMediaLibraryFileUpload::make('bukti')
                ->label("Bukti Disposisi (Opsional, Max 5MB)")
                ->multiple(false)
                ->dehydrated(true)
                ->image()
                ->collection('bukti-disposisi')
                ->preserveFilenames()
                ->maxSize(5048),
        ];
    }
    protected function handleDisposisi(array $data, Action $action): void
    {
        $user = Auth::user();
        $unitId = $user->unit_kerja_id;

        $parentDisposisi = $this->surat
            ->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->sortByDesc('tanggal_disposisi')
            ->first();

        $skipped = [];
        $successCount = 0;

        $tujuanList = $data['tujuan_disposisi'] ?? [];

        foreach ($tujuanList as $item) {
            $unitTujuanId = $item['unit_tujuan_id'];

            $alreadyExists = Disposisi::where('surat_id', $this->surat->id)
                ->where('unit_tujuan_id', $unitTujuanId)
                ->exists();

            if ($alreadyExists) {
                $unitName = UnitKerja::find($unitTujuanId)?->nama_unit ?? 'Unit';
                $skipped[] = $unitName;
                continue;
            }

            $activeJabatan = Auth::user()->getActiveJabatan();

            $jenisInstruksi = $item['jenis_instruksi'] === 'lainnya'
                ? $item['instruksi_custom']
                : $item['jenis_instruksi'];

            $disposisi = Disposisi::create([
                'surat_id' => $this->surat->id,
                'unit_tujuan_id' => $unitTujuanId,
                'user_pembuat_id' => Auth::id(),
                'user_pegawai_jabatan_id' => $activeJabatan?->id,
                'jenis_instruksi' => $jenisInstruksi,
                'sifat' => $item['sifat'],
                'catatan' => $item['catatan'],
                'status_disposisi' => 'BARU',
                'tanggal_disposisi' => now(),
                'parent_disposisi_id' => $parentDisposisi?->id,
            ]);

            $targetUsers = \App\Models\User::ofUnitKerja($unitTujuanId)->get();
            if ($targetUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Disposisi Baru')
                    ->body("Unit " . ($activeJabatan?->unitKerja?->nama_unit ?? 'Anda') . " mengirimkan disposisi surat: " . $this->surat->perihal)
                    ->info()
                    ->sendToDatabase($targetUsers);
            }

            // Lampirkan bukti yang sama ke setiap record disposisi
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
    protected function handleRespondDisposisi(array $data): void
    {
        $unitId = Auth::user()->unit_kerja_id;

        $disposisi = $this->getActiveDisposisi();
        // $this->surat->disposisis
        //     ->where('unit_tujuan_id', $unitId)
        //     ->sortByDesc('tanggal_disposisi')
        //     ->first();

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
                    ->body("Unit " . Auth::user()->unitKerja?->nama_unit . " telah menyelesaikan disposisi pada surat: " . $this->surat->perihal)
                    ->success()
                    ->sendToDatabase($pembuat);
            }
        }

        $this->updateStatusSurat();

        $this->refreshPage('Disposisi diperbarui', null);
    }

    protected function canDisposisi(): bool
    {
        $unitId = Auth::user()->unit_kerja_id;
        return $this->suratUnit !== null || $this->surat->disposisis->contains('unit_tujuan_id', $unitId);
    }

    protected function canRespondDisposisi(): bool
    {
        $unitId = Auth::user()->unit_kerja_id;
        return $this->surat->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->where('status_disposisi', '!=', 'SELESAI')
            ->isNotEmpty();
    }

    /**
     * Get the most recent active Disposisi targeted to the logged-in user's unit.
     */
    protected function getActiveDisposisi()
    {
        $unitId = \Illuminate\Support\Facades\Auth::user()->unit_kerja_id;

        return $this->surat->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->sortByDesc('tanggal_disposisi')
            ->first();
    }
}
