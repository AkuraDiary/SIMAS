<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasSuratTimeline
{
    public function getTimelineDataProperty(): array
    {
        $timeline = [];


        // 1. Surat Dibuat
        $timeline[] = [
            'title' => 'Surat Dibuat',
            'actor' => $this->surat->userPegawaiJabatan?->pegawai->nama_lengkap ?? $this->surat->pengirim_nama ?? 'Sistem',
            'unit' => $this->surat->unitPengirim?->nama_unit ?? 'Eksternal',
            'catatan' => null,
            'date' => $this->surat->created_at,
            'color' => 'bg-gray-500 ring-gray-100 dark:ring-gray-900',
            'icon' => 'heroicon-m-document-plus',
        ];

        // 2. Riwayat Persetujuan (Includes DISETUJUI, DITOLAK, DIKEMBALIKAN)
               // 2. Riwayat Persetujuan (Includes DISETUJUI, DITOLAK, DIKEMBALIKAN, DITERUSKAN)
               foreach ($this->surat->riwayats as $riwayat) {

                // Buat Judul Timeline Sangat Eksplisit!
                $title = match ($riwayat->status) {
                    'DISETUJUI' => 'Disetujui, dikirim ke: ' . ($riwayat->unitTujuan?->nama_unit ?? '-'),
                    'DITERUSKAN' => 'Diteruskan ke: ' . ($riwayat->unitTujuan?->nama_unit ?? '-'),
                    'DIKEMBALIKAN' => 'Dikembalikan ke: ' . ($riwayat->unitTujuan?->nama_unit ?? '-'),
                    'MENUNGGU' => 'Menunggu persetujuan: ' . ($riwayat->unitTujuan?->nama_unit ?? '-'),
                    'DITOLAK' => 'Ditolak permanen oleh: ' . ($riwayat->unitAsal?->nama_unit ?? '-'),
                    'REVISI' => 'Dikembalikan ke pembuat: ' . ($riwayat->unitTujuan?->nama_unit ?? '-'),
                    default => $riwayat->status,
                };

                $icon = match ($riwayat->status) {
                    'DISETUJUI' => 'heroicon-m-check-circle',
                    'DITERUSKAN' => 'heroicon-m-arrow-right-circle',
                    'DITOLAK' => 'heroicon-m-x-circle',
                    'REVISI' => 'heroicon-m-pencil-square',
                    'DIPERBARUI' => 'heroicon-m-arrow-up-tray',
                    'DIKEMBALIKAN' => 'heroicon-m-arrow-path',
                    default => 'heroicon-m-clock',
                };

                $bgColor = match ($riwayat->status) {
                    'DISETUJUI' => 'bg-emerald-500 ring-emerald-100 dark:ring-emerald-900',
                    'DITERUSKAN' => 'bg-blue-500 ring-blue-100 dark:ring-blue-900',
                    'DITOLAK' => 'bg-red-500 ring-red-100 dark:ring-red-900',
                    'REVISI' => 'bg-amber-500 ring-amber-100 dark:ring-amber-900',
                    'DIKEMBALIKAN' => 'bg-amber-500 ring-amber-100 dark:ring-amber-900',
                    'DIPERBARUI' => 'bg-blue-500 ring-blue-100 dark:ring-blue-900',
                    default => 'bg-gray-400 ring-gray-100 dark:ring-gray-900',
                };

                $timeline[] = [
                    'title' =>  $title,
                    'actor' => $riwayat->aktor?->nama_lengkap ?? '',
                    'unit' => $riwayat->unitAsal?->nama_unit, // Siapa yang mengambil aksi ini
                    'catatan' => $riwayat->catatan,
                    'date' => $riwayat->actioned_at ?? $riwayat->created_at,
                    'color' => $bgColor,
                    'icon' => $icon,
                ];
            }

        // 3. Disposisi
        foreach ($this->surat->disposisis as $disposisi) {
            $timeline[] = [
                'title' => 'Disposisi ke: ' . ($disposisi->unitTujuan?->nama_unit ?? ''),
                'instruksi' => $disposisi->jenis_instruksi,
                'actor' => $disposisi->pembuat?->nama_lengkap ?? 'Sistem',
                'unit' => $disposisi->unitPembuat?->nama_unit ?? '',
                'catatan' => $disposisi->catatan,
                'date' => $disposisi->created_at,
                'color' => 'bg-blue-500 ring-blue-100 dark:ring-blue-900',
                'icon' => 'heroicon-m-paper-airplane',
            ];
        }

        // 4. Komentar (Diskusi)
        foreach ($this->surat->komentars as $komentar) {
            $timeline[] = [
                'title' => 'Komentar',
                'actor' => $komentar->user?->nama_lengkap ?? '',
                'unit' => $komentar->unitKerja?->nama_unit ?? '',
                'catatan' => $komentar->pesan,
                'date' => $komentar->created_at,
                'color' => 'bg-purple-500 ring-purple-100 dark:ring-purple-900',
                'icon' => 'heroicon-m-chat-bubble-left-ellipsis',
            ];
        }

        // 5. Arsip Surat (Hanya Tampil Jika Unit Kerja User = Unit Kerja Pengarsip)
        $userUnitId = Auth::user()->unit_kerja_id;
        foreach ($this->surat->arsipSurats as $arsip) {
            if ($arsip->unit_kerja_id === $userUnitId) {
                $timeline[] = [
                    'title' => 'Surat Diarsipkan (' . ($arsip->kategoriArsip?->nama ?? 'Tanpa Kategori') . ')',
                    'actor' => 'Sistem',
                    'unit' => $arsip->unitKerja?->nama_unit ?? '',
                    'catatan' => $arsip->catatan,
                    'date' => $arsip->tanggal_arsip ?? $arsip->created_at,
                    'color' => 'bg-indigo-500 ring-indigo-100 dark:ring-indigo-900',
                    'icon' => 'heroicon-m-archive-box',
                ];
            }
        }

        // Sort secara kronologis (dari yang terlama sampai terbaru)
        usort($timeline, fn($a, $b) => $a['date'] <=> $b['date']);

        return $timeline;
    }
}
