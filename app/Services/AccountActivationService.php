<?php

namespace App\Services;


use App\Mail\AccountActivationMail;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountActivationService
{
    /**
     * Buat tautan aktivasi terenkripsi (AES-256) dengan payload sesi, user_id, dan kedaluwarsa.
     */
    public function generateActivationUrl(User $user, string $channel = 'whatsapp'): string
    {
        $session = Str::random(40);

        // Simpan session nonce ke kolom settings JSON user untuk single-use & invalidasi link lama
        $settings = $user->settings ?? [];
        $settings['activation_session'] = $session;
        $user->settings = $settings;
        $user->save();

        $lifetimeDays = (int) config('simas.activation.token_lifetime_days', 3);
        $payload = [
            'user_id'    => $user->id,
            'identifier' => $user->username, // NIP atau NIM
            'session'    => $session,
            'channel'    => $channel,
            'expires_at' => now()->addDays($lifetimeDays)->timestamp,
            'created_at' => now()->timestamp,
        ];

        $encryptedToken = Crypt::encryptString(json_encode($payload));

        return url('/aktivasi?token=' . urlencode($encryptedToken));
    }

    /**
     * Kirim link aktivasi via WhatsApp atau Email sesuai channel yang dipilih.
     */
    public function sendActivationLink(User $user, string $channel): array
    {
        $channel = strtolower(trim($channel));

        if ($channel === 'whatsapp') {
            return $this->sendViaWhatsApp($user);
        }

        if ($channel === 'email') {
            return $this->sendViaEmail($user);
        }

        return [
            'status' => false,
            'reason' => "Saluran pengiriman '{$channel}' tidak didukung.",
        ];
    }

    protected function sendViaWhatsApp(User $user): array
    {
        if (blank($user->phone)) {
            return [
                'status' => false,
                'reason' => 'Nomor WhatsApp pengguna belum terdaftar.',
            ];
        }

        $url = $this->generateActivationUrl($user, 'whatsapp');
        $nama = $user->nama_lengkap ?? $user->username;
        $identifier = $user->username;

        $message = "Halo Bapak/Ibu/Saudara *{$nama}* ({$identifier}),\n\n"
            . "Akun SIMAS Anda telah dibuat. Silakan lakukan aktivasi akun Anda melalui tautan berikut:\n\n"
            . "{$url}\n\n"
            . "Tautan aktivasi ini berlaku selama 3 hari. Jangan bagikan tautan ini kepada siapapun demi keamanan akun Anda.\n\n"
            . "Salam hangat,\n*SIMAS - Sistem Informasi Manajemen Arsip & Surat*";

        return app(FonnteService::class)->send($user->phone, $message);
    }

    protected function sendViaEmail(User $user): array
    {
        if (blank($user->email)) {
            return [
                'status' => false,
                'reason' => 'Alamat email pengguna belum terdaftar.',
            ];
        }

        try {
            $url = $this->generateActivationUrl($user, 'email');
            Mail::to($user->email)->send(new AccountActivationMail($user, $url));

            return [
                'status' => true,
                'reason' => 'Email aktivasi berhasil dikirim.',
            ];
        } catch (\Throwable $e) {
            Log::error('[AccountActivationService] Gagal mengirim email aktivasi: ' . $e->getMessage());

            return [
                'status' => false,
                'reason' => 'Gagal mengirim email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validasi token terenkripsi dari query parameter URL.
     */
    public function validateToken(string $token): array
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);
        } catch (DecryptException $e) {
            return [
                'valid'  => false,
                'reason' => 'Tautan aktivasi tidak valid atau telah rusak.',
            ];
        }

        if (! is_array($payload) || ! isset($payload['user_id'], $payload['session'], $payload['expires_at'])) {
            return [
                'valid'  => false,
                'reason' => 'Format payload tautan aktivasi tidak valid.',
            ];
        }

        // Cek apakah tautan sudah kedaluwarsa
        if (now()->timestamp > $payload['expires_at']) {
            return [
                'valid'  => false,
                'reason' => 'Tautan aktivasi telah kedaluwarsa. Silakan hubungi admin untuk mendapatkan tautan baru.',
            ];
        }

        $user = User::find($payload['user_id']);
        if (! $user) {
            return [
                'valid'  => false,
                'reason' => 'Pengguna untuk akun ini tidak ditemukan dalam sistem.',
            ];
        }

        // Cek jika akun sudah aktif sebelumnya
        if ($user->is_active) {
            return [
                'valid'          => false,
                'already_active' => true,
                'user'           => $user,
                'reason'         => 'Akun Anda sudah aktif sebelumnya. Anda dapat langsung login ke dalam sistem.',
            ];
        }

        // Cek apakah session masih cocok (jika admin sudah mengirim ulang link baru, link lama batal)
        $currentSession = $user->settings['activation_session'] ?? null;
        if ($currentSession !== $payload['session']) {
            return [
                'valid'  => false,
                'reason' => 'Tautan aktivasi ini sudah tidak berlaku karena tautan baru telah diterbitkan. Gunakan tautan aktivasi terbaru.',
            ];
        }

        return [
            'valid'   => true,
            'user'    => $user,
            'payload' => $payload,
        ];
    }

    /**
     * Eksekusi aktivasi user: ubah is_active menjadi true, update password jika ada, bersihkan sesi link.
     */
    public function activateUser(User $user, ?string $newPassword = null): bool
    {
        $user->is_active = true;

        if (filled($newPassword)) {
            $user->password = Hash::make($newPassword);
        }

        $settings = $user->settings ?? [];
        unset($settings['activation_session']); // Link tidak bisa digunakan lagi
        $settings['activated_at'] = now()->toIso8601String();
        $user->settings = $settings;

        return $user->save();
    }
}
