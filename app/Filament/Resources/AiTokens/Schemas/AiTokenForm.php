<?php

namespace App\Filament\Resources\AiTokens\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AiTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider')
                    ->label('Penyedia Layanan AI')
                    ->options([
                        'gemini' => 'Google Gemini',
                        'ollama' => 'Ollama Lokal / Remote',
                    ])
                    ->default('gemini')
                    ->required()
                    ->live(),

                TextInput::make('label')
                    ->label('Label / Nama Konfigurasi')
                    ->placeholder('Contoh: Gemini Produksi Kampus')
                    ->required()
                    ->maxLength(255),

                TextInput::make('api_key')
                    ->label('Kunci API (API Key)')
                    ->password()
                    ->revealable()
                    ->placeholder('Masukkan kunci API rahasia')
                    ->required(fn (Get $get): bool => $get('provider') === 'gemini')
                    ->helperText(fn (Get $get): string => $get('provider') === 'gemini'
                        ? 'Wajib diisi untuk Google Gemini.'
                        : 'Opsional jika server Ollama dilindungi token/Bearer token.'),

                TextInput::make('model')
                    ->label('Nama Model')
                    ->placeholder(fn (Get $get): string => $get('provider') === 'ollama' ? 'llama3.2' : 'gemini-2.5-flash')
                    ->helperText('Kosongkan untuk menggunakan model default dari sistem/konfigurasi.'),

                TextInput::make('base_url')
                    ->label('Base URL API')
                    ->placeholder(fn (Get $get): string => $get('provider') === 'ollama' ? 'http://localhost:11434' : 'https://generativelanguage.googleapis.com/v1beta')
                    ->required(fn (Get $get): bool => $get('provider') === 'ollama')
                    ->helperText(fn (Get $get): string => $get('provider') === 'ollama'
                        ? 'Wajib diisi untuk endpoint server Ollama (contoh: http://localhost:11434).'
                        : 'Opsional untuk Gemini, biarkan kosong untuk menggunakan URL resmi.'),

                Toggle::make('is_active')
                    ->label('Aktifkan Sebagai Token Utama')
                    ->default(false)
                    ->helperText('Hanya satu token yang dapat aktif dalam satu waktu. Mengaktifkan token ini akan menonaktifkan token lainnya.'),
            ]);
    }
}
