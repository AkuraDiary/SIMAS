<?php

namespace App\Filament\Resources\AiTokens\Pages;

use App\Filament\Resources\AiTokens\AiTokenResource;
use App\Services\AiDraftManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAiTokens extends ListRecords
{
    protected static string $resource = AiTokenResource::class;

    public function getSubheading(): ?string
    {
        $activeToken = \App\Models\AiToken::query()->active()->first();
        if ($activeToken) {
            return "Sumber Token Aktif: {$activeToken->label} ({$activeToken->provider})";
        }

        return 'Sumber Token Aktif: Fallback Environment (.env)';
    }

    protected function getHeaderActions(): array
    {
        $isEnabled = AiDraftManager::isFeatureEnabled();

        return [
            Action::make('toggle_global_feature')
                ->label($isEnabled ? 'Asisten AI: AKTIF' : 'Asisten AI: NONAKTIF')
                ->icon($isEnabled ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($isEnabled ? 'success' : 'danger')
                ->tooltip('Klik untuk beralih status aktif/nonaktif fitur asistensi AI bagi staf.')
                ->requiresConfirmation()
                ->modalHeading($isEnabled ? 'Nonaktifkan Asisten AI Global?' : 'Aktifkan Asisten AI Global?')
                ->modalDescription($isEnabled
                    ? 'Jika dinonaktifkan, staf tidak dapat melihat tombol Bantuan AI pada formulir naskah dinas.'
                    : 'Jika diaktifkan, staf dapat menggunakan asisten AI untuk draf surat.')
                ->modalSubmitActionLabel($isEnabled ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan')
                ->action(function () {
                    $current = AiDraftManager::isFeatureEnabled();
                    $next = ! $current;
                    AiDraftManager::setFeatureEnabled($next);

                    Notification::make()
                        ->title($next ? 'Fitur Asisten AI Diaktifkan' : 'Fitur Asisten AI Dinonaktifkan')
                        ->body($next
                            ? 'Tombol Bantuan AI kini aktif dan dapat digunakan staf.'
                            : 'Tombol Bantuan AI kini disembunyikan dari staf.')
                        ->status($next ? 'success' : 'warning')
                        ->send();
                }),

            CreateAction::make()
                ->label('Tambah Token Baru')
                ->modalHeading('Tambah Token AI Baru')
                ->modalSubmitActionLabel('Simpan'),
        ];
    }
}
