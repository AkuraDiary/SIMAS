<?php

namespace App\Services;

use App\Models\Disposisi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected FonnteService $fonnte;

    public function __construct(FonnteService $fonnte)
    {
        $this->fonnte = $fonnte;
    }

    /**
     * Kirim notifikasi Surat Masuk Baru ke staf unit tujuan yang mengaktifkan notifikasi WA.
     */
    public function notifySuratMasuk(Surat $surat, iterable $targetUsers, ?string $catatan = null): void
    {
        $recipients = $this->filterRecipients($targetUsers, 'surat_masuk');

        if ($recipients->isEmpty()) {
            return;
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $unitPengirim = $surat->unitPengirim?->nama_unit ?? $surat->pengirim_nama ?? 'Pihak Luar/Pengaju';
        $nomorSurat = $surat->nomor_surat ?? $surat->nomor_surat_eksternal ?? '(Belum Ada Nomor)';
        $tanggal = $surat->tanggal_kirim ? \Carbon\Carbon::parse($surat->tanggal_kirim)->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y H:i');

        foreach ($recipients as $recipient) {
            $nama = $recipient->nama_lengkap ?? $recipient->username;
            $phone = $recipient->getFormattedPhoneForWhatsApp();

            $message = "📩 *SIMAS: Surat Masuk Baru*\n"
                . "Halo, *{$nama}*!\n\n"
                . "Unit Anda telah menerima surat masuk baru pada sistem SIMAS:\n"
                . "• *Nomor Surat*: {$nomorSurat}\n"
                . "• *Perihal*: {$surat->perihal}\n"
                . "• *Pengirim*: {$unitPengirim}\n"
                . "• *Waktu*: {$tanggal}\n";

            if (filled($catatan)) {
                $message .= "• *Catatan*: {$catatan}\n";
            }

            $message .= "\nSilakan buka SIMAS untuk meninjau dan mendisposisikan surat:\n"
                . "🔗 {$appUrl}/staf-unit/surat-masuk-unit\n\n"
                . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

            $this->fonnte->send($phone, $message);
        }
    }

    /**
     * Kirim notifikasi Disposisi Baru ke staf unit penerima disposisi.
     */
    public function notifyDisposisiBaru(Disposisi $disposisi, iterable $targetUsers): void
    {
        $recipients = $this->filterRecipients($targetUsers, 'surat_masuk');

        if ($recipients->isEmpty()) {
            return;
        }

        $surat = $disposisi->surat;
        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $pemberiUnit = $disposisi->userPegawaiJabatan?->unitKerja?->nama_unit ?? $disposisi->pembuat?->unit_kerja?->nama_unit ?? 'Pimpinan Unit';
        $pemberiNama = $disposisi->pembuat?->nama_lengkap ?? $disposisi->pembuat?->username ?? 'Atasan';
        $sifat = $disposisi->sifat ?? 'BIASA';
        $instruksi = $disposisi->catatan ?? $disposisi->jenis_instruksi ?? '-';

        foreach ($recipients as $recipient) {
            $nama = $recipient->nama_lengkap ?? $recipient->username;
            $phone = $recipient->getFormattedPhoneForWhatsApp();

            $message = "📋 *SIMAS: Disposisi Baru*\n"
                . "Halo, *{$nama}*!\n\n"
                . "Unit Anda menerima instruksi lembar disposisi baru:\n"
                . "• *Perihal Surat*: {$surat?->perihal}\n"
                . "• *Asal Disposisi*: {$pemberiUnit} ({$pemberiNama})\n"
                . "• *Sifat*: *{$sifat}*\n"
                . "• *Instruksi/Catatan*: {$instruksi}\n\n"
                . "Silakan buka SIMAS untuk melihat lembar disposisi dan menindaklanjuti:\n"
                . "🔗 {$appUrl}/staf-unit/surat-masuk-unit\n\n"
                . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

            $this->fonnte->send($phone, $message);
        }
    }

    /**
     * Kirim notifikasi kepada pembuat surat bahwa surat memerlukan revisi.
     */
    public function notifySuratRevisi(Surat $surat, ?User $actor = null, ?string $catatan = null): void
    {
        $pembuat = $surat->pembuat;

        if (!$pembuat || !$pembuat->wantsNotification('surat_revisi', 'whatsapp')) {
            return;
        }

        $phone = $pembuat->getFormattedPhoneForWhatsApp();
        if (!$phone) {
            return;
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $namaPembuat = $pembuat->nama_lengkap ?? $pembuat->username;
        $namaAktor = $actor?->nama_lengkap ?? $actor?->username ?? 'Pemeriksa Surat';
        $nomorSurat = $surat->nomor_surat ?? '(Draft Pengajuan)';
        $catatanRevisi = filled($catatan) ? $catatan : 'Silakan tinjau kembali kelengkapan draf surat.';

        $message = "⚠️ *SIMAS: Permintaan Revisi Surat*\n"
            . "Halo, *{$namaPembuat}*!\n\n"
            . "Surat yang Anda ajukan memerlukan perbaikan / revisi:\n"
            . "• *Nomor Surat*: {$nomorSurat}\n"
            . "• *Perihal*: {$surat->perihal}\n"
            . "• *Ditinjau Oleh*: {$namaAktor}\n"
            . "• *Catatan Revisi*: {$catatanRevisi}\n\n"
            . "Silakan masuk ke aplikasi SIMAS untuk memperbarui draf dokumen:\n"
            . "🔗 {$appUrl}/surats\n\n"
            . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

        $this->fonnte->send($phone, $message);
    }

    /**
     * Kirim notifikasi kepada pembuat surat / pengaju bahwa surat telah selesai disetujui / terbitan siap.
     */
    public function notifySuratSelesai(Surat $surat, ?User $recipient = null, ?string $catatan = null): void
    {
        $targetUser = $recipient ?? $surat->pembuat;

        if (!$targetUser || !$targetUser->wantsNotification('surat_selesai', 'whatsapp')) {
            return;
        }

        $phone = $targetUser->getFormattedPhoneForWhatsApp();
        if (!$phone) {
            return;
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $nama = $targetUser->nama_lengkap ?? $targetUser->username;
        $nomorSurat = $surat->nomor_surat ?? '(Dokumen Final)';

        $message = "✅ *SIMAS: Surat Selesai & Disetujui*\n"
            . "Halo, *{$nama}*!\n\n"
            . "Kabar baik! Surat Anda telah selesai diproses dan disetujui:\n"
            . "• *Nomor Surat*: {$nomorSurat}\n"
            . "• *Perihal*: {$surat->perihal}\n"
            . "• *Status*: *SELESAI*\n";

        if (filled($catatan)) {
            $message .= "• *Catatan*: {$catatan}\n";
        }

        $message .= "\nDokumen resmi dapat diunduh melalui aplikasi SIMAS:\n"
            . "🔗 {$appUrl}/surats\n\n"
            . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

        $this->fonnte->send($phone, $message);
    }

    /**
     * Kirim notifikasi kepada pembuat disposisi bahwa disposisi telah selesai ditindaklanjuti.
     */
    public function notifyDisposisiSelesai(Disposisi $disposisi, ?string $catatanRespon = null): void
    {
        $pembuat = $disposisi->pembuat;

        if (!$pembuat || !$pembuat->wantsNotification('surat_selesai', 'whatsapp')) {
            return;
        }

        $phone = $pembuat->getFormattedPhoneForWhatsApp();
        if (!$phone) {
            return;
        }

        $surat = $disposisi->surat;
        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $nama = $pembuat->nama_lengkap ?? $pembuat->username;
        $unitPelaksana = auth()->user()?->unitKerja?->nama_unit ?? 'Unit Penerima';
        $laporan = filled($catatanRespon) ? $catatanRespon : 'Instruksi disposisi telah dilaksanakan dengan baik.';

        $message = "✅ *SIMAS: Disposisi Telah Selesai*\n"
            . "Halo, *{$nama}*!\n\n"
            . "Disposisi yang Anda instruksikan telah selesai ditindaklanjuti:\n"
            . "• *Perihal Surat*: {$surat?->perihal}\n"
            . "• *Diselesaikan Oleh*: {$unitPelaksana}\n"
            . "• *Laporan Tindak Lanjut*: {$laporan}\n\n"
            . "Silakan periksa perkembangan surat pada aplikasi SIMAS:\n"
            . "🔗 {$appUrl}/staf-unit/surat-masuk-unit\n\n"
            . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

        $this->fonnte->send($phone, $message);
    }

    /**
     * Kirim notifikasi penolakan permanen surat kepada pembuat surat.
     */
    public function notifySuratDitolak(Surat $surat, ?User $actor = null, ?string $catatan = null): void
    {
        $pembuat = $surat->pembuat;

        if (!$pembuat || !$pembuat->wantsNotification('surat_ditolak', 'whatsapp')) {
            return;
        }

        $phone = $pembuat->getFormattedPhoneForWhatsApp();
        if (!$phone) {
            return;
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $namaPembuat = $pembuat->nama_lengkap ?? $pembuat->username;
        $namaAktor = $actor?->nama_lengkap ?? $actor?->username ?? 'Pihak Berwenang';
        $alasan = filled($catatan) ? $catatan : 'Permohonan tidak memenuhi kriteria yang disyaratkan.';

        $message = "❌ *SIMAS: Surat Pengajuan Ditolak*\n"
            . "Halo, *{$namaPembuat}*!\n\n"
            . "Surat pengajuan Anda telah ditolak secara permanen:\n"
            . "• *Perihal*: {$surat->perihal}\n"
            . "• *Ditolak Oleh*: {$namaAktor}\n"
            . "• *Alasan Penolakan*: {$alasan}\n\n"
            . "Untuk informasi lebih rinci, silakan kunjungi aplikasi SIMAS:\n"
            . "🔗 {$appUrl}/surats\n\n"
            . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

        $this->fonnte->send($phone, $message);
    }

    /**
     * Filter daftar penerima berdasarkan preferensi event dan ketersediaan nomor telepon WhatsApp.
     */
    protected function filterRecipients(iterable $users, string $event)
    {
        return collect($users)->filter(function ($user) use ($event) {
            if (!($user instanceof User)) {
                return false;
            }

            // Cek preferensi notifikasi (otomatis menolak tipe_entitas ADMIN dan toggle non-aktif)
            if (!$user->wantsNotification($event, 'whatsapp')) {
                return false;
            }

            // Pastikan nomor telepon WhatsApp terisi dan valid
            return !empty($user->getFormattedPhoneForWhatsApp());
        });
    }
}
