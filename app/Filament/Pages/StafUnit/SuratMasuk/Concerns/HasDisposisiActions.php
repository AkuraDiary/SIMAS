<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use App\Models\Disposisi;
use App\Models\UnitKerja;
use App\Models\UserPegawaiJabatan;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
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
                    Radio::make('tipe_tujuan')
                        ->label('Cakupan Tujuan Disposisi')
                        ->options([
                            'EKSTERNAL' => 'Disposisi Antar-Unit Kerja',
                            'INTERNAL'  => 'Disposisi Internal Unit (Staf Unit Sendiri)',
                        ])
                        ->default('EKSTERNAL')
                        ->inline()
                        ->live()
                        ->columnSpanFull(),

                    Select::make('unit_tujuan_id')
                        ->label('Unit Tujuan')
                        ->options(
                            fn() => UnitKerja::query()->where('id', '<>', Auth::user()->unit_kerja_id)
                                ->pluck('nama_unit', 'id')
                        )
                        ->searchable()
                        ->visible(fn(Get $get) => $get('tipe_tujuan') !== 'INTERNAL')
                        ->required(fn(Get $get) => $get('tipe_tujuan') !== 'INTERNAL'),

                    Select::make('user_pegawai_jabatan_id')
                        ->label('Pilih Staf Internal Penerima')
                        ->options(function () {
                            $unitId = Auth::user()->unit_kerja_id;
                            $myActiveJabatanId = Auth::user()->getActiveJabatan()?->id;

                            return UserPegawaiJabatan::query()
                                ->where('unit_kerja_id', $unitId)
                                ->where('status_jabatan', 'AKTIF')
                                ->where('id', '!=', $myActiveJabatanId)
                                ->with(['pegawai', 'jabatan'])
                                ->get()
                                ->mapWithKeys(function ($upj) {
                                    $nama = $upj->pegawai?->nama_lengkap ?? 'Pegawai';
                                    $jab = $upj->jabatan?->nama_jabatan ?? 'Jabatan';
                                    return [$upj->id => "{$nama} - {$jab}"];
                                });
                        })
                        ->searchable()
                        ->visible(fn(Get $get) => $get('tipe_tujuan') === 'INTERNAL')
                        ->required(fn(Get $get) => $get('tipe_tujuan') === 'INTERNAL'),

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
                        ->required(fn(Get $get) => $get('jenis_instruksi') === 'lainnya')
                        ->visible(fn(Get $get) => $get('jenis_instruksi') === 'lainnya'),

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
                ->columns(2)
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
            $isInternal = ($item['tipe_tujuan'] ?? 'EKSTERNAL') === 'INTERNAL';
            $unitTujuanId = $isInternal ? $unitId : ($item['unit_tujuan_id'] ?? null);
            $targetJabatanId = $isInternal ? ($item['user_pegawai_jabatan_id'] ?? null) : null;

            if (!$unitTujuanId) {
                continue;
            }

            $alreadyExists = Disposisi::where('surat_id', $this->surat->id)
                ->where('unit_tujuan_id', $unitTujuanId)
                ->when($targetJabatanId, fn($q) => $q->where('user_pegawai_jabatan_id', $targetJabatanId), fn($q) => $q->whereNull('user_pegawai_jabatan_id'))
                ->exists();

            if ($alreadyExists) {
                if ($isInternal && $targetJabatanId) {
                    $upj = UserPegawaiJabatan::with('pegawai')->find($targetJabatanId);
                    $skipped[] = $upj?->pegawai?->nama_lengkap ?? 'Staf Internal';
                } else {
                    $unitName = UnitKerja::find($unitTujuanId)?->nama_unit ?? 'Unit';
                    $skipped[] = $unitName;
                }
                continue;
            }

            $activeJabatan = Auth::user()->getActiveJabatan();

            $jenisInstruksi = ($item['jenis_instruksi'] ?? '') === 'lainnya'
                ? ($item['instruksi_custom'] ?? 'Lainnya')
                : ($item['jenis_instruksi'] ?? 'tindaklanjuti');

            $disposisi = Disposisi::create([
                'surat_id' => $this->surat->id,
                'unit_tujuan_id' => $unitTujuanId,
                'user_pembuat_id' => Auth::id(),
                'user_pegawai_jabatan_id' => $targetJabatanId,
                'jenis_instruksi' => $jenisInstruksi,
                'sifat' => $item['sifat'] ?? 'BIASA',
                'catatan' => $item['catatan'] ?? null,
                'status_disposisi' => 'BARU',
                'tanggal_disposisi' => now(),
                'parent_disposisi_id' => $parentDisposisi?->id,
            ]);

            if ($isInternal && $targetJabatanId) {
                $targetUpj = UserPegawaiJabatan::with('pegawai.user')->find($targetJabatanId);
                $targetUser = $targetUpj?->pegawai?->user;
                if ($targetUser) {
                    Notification::make()
                        ->title('Disposisi Internal Unit')
                        ->body("Pimpinan " . ($activeJabatan?->jabatan?->nama_jabatan ?? 'Unit') . " mendisposisikan surat kepada Anda: " . $this->surat->perihal)
                        ->info()
                        ->viewData([
                            'unit_kerja_id' => (int) $unitId,
                            'surat_id'      => $this->surat->id,
                        ])
                        ->sendToDatabase($targetUser);

                    app(\App\Services\WhatsAppNotificationService::class)->notifyDisposisiBaru($disposisi, collect([$targetUser]));
                }
            } else {
                $targetUsers = \App\Models\User::ofUnitKerja($unitTujuanId)->get();
                if ($targetUsers->isNotEmpty()) {
                    Notification::make()
                        ->title('Disposisi Baru')
                        ->body("Unit " . ($activeJabatan?->unitKerja?->nama_unit ?? 'Anda') . " mengirimkan disposisi surat: " . $this->surat->perihal)
                        ->info()
                        ->viewData([
                            'unit_kerja_id' => (int) $unitTujuanId,
                            'surat_id'      => $this->surat->id,
                        ])
                        ->sendToDatabase($targetUsers);

                    app(\App\Services\WhatsAppNotificationService::class)->notifyDisposisiBaru($disposisi, $targetUsers);
                }
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
                    ->viewData([
                        'unit_kerja_id' => (int) ($disposisi->unit_pembuat_id ?? $this->surat->unit_pengirim_id),
                        'surat_id'      => $this->surat->id,
                    ])
                    ->sendToDatabase($pembuat);

                app(\App\Services\WhatsAppNotificationService::class)->notifyDisposisiSelesai($disposisi, $data['catatan_respon'] ?? null);
            }
        }

        $this->updateStatusSurat();

        $this->refreshPage('Disposisi diperbarui', null);
    }

    protected function canDisposisi(): bool
    {
        $unitId = Auth::user()->unit_kerja_id;
        if (!Auth::user()->canDisposisiUnit($unitId)) {
            return false;
        }

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
