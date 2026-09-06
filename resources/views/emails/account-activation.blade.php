<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun SIMAS</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 30px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">SIMAS</h1>
                            <p style="color: #dbeafe; margin: 6px 0 0 0; font-size: 14px;">Sistem Informasi Manajemen Arsip & Surat</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1f2937; margin: 0 0 16px 0; font-size: 18px;">Halo, {{ $user->nama_lengkap ?? $user->username }}!</h2>
                            <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;">
                                Akun Anda pada sistem <strong>SIMAS</strong> telah berhasil didaftarkan dengan nomor identitas (NIP/NIM): <strong>{{ $user->username }}</strong>.
                            </p>
                            <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 28px 0;">
                                Untuk mulai mengakses dan menggunakan akun Anda, silakan klik tombol aktivasi berikut ini:
                            </p>
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ $activationUrl }}" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block;">Aktivasi Akun Sekarang</a>
                            </div>
                            <p style="color: #6b7280; font-size: 13px; line-height: 1.5; margin: 24px 0 0 0;">
                                <em>Tautan ini berlaku selama 3 hari. Jangan bagikan tautan ini kepada orang lain demi keamanan data Anda.</em>
                            </p>
                            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 32px 0 20px 0;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                Jika Anda kesulitan mengklik tombol di atas, salin dan tempel URL berikut ke peramban web Anda:<br>
                                <a href="{{ $activationUrl }}" style="color: #2563eb; word-break: break-all;">{{ $activationUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
