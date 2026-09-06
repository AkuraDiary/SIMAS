<?php

namespace App\Livewire\Auth;

use App\Services\AccountActivationService;
use Livewire\Component;

class AktivasiAkun extends Component
{
    public ?string $token = null;
    public string $status = 'loading'; // 'loading', 'confirm', 'success', 'error'
    public string $errorMessage = '';
    public bool $alreadyActive = false;

    public ?string $namaUser = null;
    public ?string $identifierExpected = null;

    // Form inputs
    public string $input_identifier = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';
    public bool $requireNipNim = true;

    protected function rules(): array
    {
        return [
            'input_identifier' => 'required|string',
            'new_password'     => 'nullable|min:8|confirmed',
        ];
    }

    protected $messages = [
        'input_identifier.required' => 'NIP / NIM wajib dimasukkan untuk konfirmasi.',
        'new_password.min'          => 'Password baru minimal 8 karakter.',
        'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
    ];

    public function mount(AccountActivationService $service): void
    {
        $this->token = request()->query('token');

        if (blank($this->token)) {
            $this->status = 'error';
            $this->errorMessage = 'Tautan aktivasi tidak ditemukan. Pastikan Anda membuka tautan lengkap dari WhatsApp atau Email Anda.';
            return;
        }

        $validation = $service->validateToken($this->token);

        if (! $validation['valid']) {
            $this->status = 'error';
            $this->errorMessage = $validation['reason'];
            $this->alreadyActive = $validation['already_active'] ?? false;
            return;
        }

        $user = $validation['user'];
        $this->namaUser = $user->nama_lengkap ?? $user->username;
        $this->identifierExpected = $user->username;

        // Periksa toggle konfirmasi NIP/NIM dari konfigurasi
        $this->requireNipNim = (bool) config('services.activation.require_nip_nim_confirmation', true);

        // Jika toggle mati (false), aktivasi langsung secara instan
        if (! $this->requireNipNim) {
            $service->activateUser($user);
            $this->status = 'success';
            return;
        }

        // Jika toggle aktif (true), minta input konfirmasi NIP/NIM
        $this->status = 'confirm';
    }

    public function konfirmasiAktivasi(AccountActivationService $service): void
    {
        $this->validate();

        // Validasi kesesuaian input NIP / NIM dengan payload token
        if (trim($this->input_identifier) !== trim((string) $this->identifierExpected)) {
            $this->addError('input_identifier', 'NIP / NIM yang Anda masukkan tidak sesuai dengan data akun pada tautan aktivasi ini.');
            return;
        }

        // Validasi ulang token demi keamanan
        $validation = $service->validateToken($this->token);
        if (! $validation['valid']) {
            $this->status = 'error';
            $this->errorMessage = $validation['reason'];
            return;
        }

        $service->activateUser($validation['user'], filled($this->new_password) ? $this->new_password : null);

        $this->status = 'success';
    }

    public function render()
    {
        return view('livewire.auth.aktivasi-akun')
            ->layout('components.layouts.app', ['title' => 'Aktivasi Akun - SIMAS']);
    }
}
