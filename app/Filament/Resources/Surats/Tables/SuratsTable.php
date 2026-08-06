<?php

namespace App\Filament\Resources\Surats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Surat;
use App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat;
use App\Filament\Resources\Surats\Pages\EditSurat;
use App\Models\KategoriArsip;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use PHPUnit\TextUI\Configuration\SourceFilter;

class SuratsTable
{

    public string $scope = 'masuk';

    public function mount(): void
    {
        $this->scope = request()->get('scope', 'masuk');
    }


    public static function configure(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('nomor_agenda')
                    ->searchable(),
                TextColumn::make('nomor_surat')
                    ->searchable(),
                TextColumn::make('perihal')
                    ->searchable(),
                TextColumn::make('tanggal_buat')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('tanggal_kirim')
                    ->dateTime()
                    ->sortable(),


                TextColumn::make('status_surat')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->scope != 'arsip'),

                TextColumn::make('arsip_kategori')
                    ->label('Diarsipkan Di')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->scope === 'arsip')
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;

                        $arsip = $record->arsipSurats
                            ->firstWhere('unit_kerja_id', $unitId);

                        return $arsip?->kategoriArsip?->nama ?? '-';
                    })
                    ->color('success'),


                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('kategori_arsip_id')
                    ->label('Kategori Arsip')
                    ->options(function () {
                        return KategoriArsip::query()
                            ->where('unit_kerja_id', Auth::user()->unit_kerja_id)
                            ->pluck('nama', 'id');
                    })
                    ->query(function (Builder $query, array $data) {

                        $value = $data['value'] ?? null;


                        // dd($value);
                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('arsipSurats', function ($q) use ($value) {
                            $q->where('kategori_arsip_id', $value);
                        });
                    })

                    ->visible(fn($livewire) => $livewire->scope === 'arsip')


            ])
            ->recordActions([
                EditAction::make()->visible(fn($record) => $record->status_surat === 'DRAFT'),
                DeleteAction::make()->visible(fn($record) => $record->status_surat === 'DRAFT'),

                \Filament\Actions\Action::make('approve')
                    ->label('Setujui / TTD')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'persetujuan' && $record->status_surat === 'DIPROSES')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->placeholder('Catatan atau catatan persetujuan...'),
                    ])
                    ->action(function (Surat $record, array $data): void {
                        $activeRiwayat = $record->riwayats()
                            ->where('status', 'MENUNGGU')
                            ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                            ->latest()
                            ->first();

                        if (!$activeRiwayat) {
                            \Filament\Notifications\Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                            return;
                        }

                        app(\App\Services\SuratRoutingService::class)->approveStep(
                            currentRiwayat: $activeRiwayat,
                            actor: Auth::user(),
                            isFinalStep: true,
                            isSignatureRequired: true,
                            catatan: $data['catatan'] ?? null
                        );

                        \Filament\Notifications\Notification::make()->title('Surat berhasil disetujui & ditandatangani')->success()->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'persetujuan' && $record->status_surat === 'DIPROSES')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Revisi')
                            ->required()
                            ->placeholder('Jelaskan bagian yang perlu diperbaiki...'),
                    ])
                    ->action(function (Surat $record, array $data): void {
                        $activeRiwayat = $record->riwayats()
                            ->where('status', 'MENUNGGU')
                            ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                            ->latest()
                            ->first();

                        if (!$activeRiwayat) {
                            \Filament\Notifications\Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                            return;
                        }

                        app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
                            currentRiwayat: $activeRiwayat,
                            actor: Auth::user(),
                            newStatus: 'REVISI',
                            catatan: $data['catatan']
                        );

                        \Filament\Notifications\Notification::make()->title('Surat dikembalikan untuk revisi')->warning()->send();
                    }),
            ])
            ->recordUrl(
                fn(Surat $record) => $record->status_surat === 'DRAFT'
                    ? EditSurat::getUrl(['record' => $record->id])
                    : DetailSurat::getUrl(
                        parameters: [
                            'surat' => $record->id,
                            'scope' => request('scope') ?? 'masuk',
                        ],
                        panel: 'simas'
                    )
            )

            ->toolbarActions([])
            ->emptyStateHeading('TIdak Ada Data Surat')
            ->emptyStateDescription('')
            ->persistSearchInSession()
            ->persistSortInSession();;
    }
}
