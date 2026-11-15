<?php

namespace App\Filament\Resources\AiTokens\Tables;

use App\Models\AiToken;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AiTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->label('Penyedia')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'gemini' => 'info',
                        'ollama' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'gemini' => 'Google Gemini',
                        'ollama' => 'Ollama',
                        default => $state ?? '-',
                    })
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('masked_api_key')
                    ->label('Kunci API')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($record) => $record->masked_api_key),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('set_active')
                    ->label('Aktifkan')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (AiToken $record): bool => ! $record->is_active)
                    ->action(function (AiToken $record) {
                        $record->activate();

                        Notification::make()
                            ->title('Token AI Diaktifkan')
                            ->body("Token '{$record->label}' kini aktif sebagai token utama.")
                            ->success()
                            ->send();
                    }),
                Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-m-x-mark')
                    ->color('warning')
                    ->visible(fn (AiToken $record): bool => (bool) $record->is_active)
                    ->action(function (AiToken $record) {
                        $record->deactivate();

                        Notification::make()
                            ->title('Token AI Dinonaktifkan')
                            ->body("Token '{$record->label}' kini dinonaktifkan.")
                            ->warning()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
