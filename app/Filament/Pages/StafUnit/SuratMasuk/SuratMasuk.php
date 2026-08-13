<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\Surat;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\EmbeddedTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class SuratMasuk extends Page implements HasTable
{
    use InteractsWithTable, HasTabs;


    public int $lastCount = 0;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
    }

    public function mount(): void
    {
        // for initials data
        $this->lastCount = $this->getSuratMasukCount();
    }

    protected function getSuratMasukCount(): int
    {
        $unitId = Auth::user()->unit_kerja_id;

        return Surat::query()
            ->untukUnit($unitId)
            ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                $q->where('unit_kerja_id', $unitId);
            })
            ->count();
    }


    public function hydrate(): void
    {
        $currentCount = $this->getSuratMasukCount();

        if ($this->lastCount !== 0 && $currentCount > $this->lastCount) {
            \Filament\Notifications\Notification::make()
                ->title('Surat baru masuk')
                ->info()
                ->send();
        }

        $this->lastCount = $currentCount;
    }



    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas === 'STAF';
    }
    public function getBreadcrumbs(): array
    {
        return [
            SuratMasuk::getUrl() => 'Surat Masuk',
        ];
    }
    protected static ?string $navigationLabel = 'Surat Masuk';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Inbox;
    protected static ?string $slug = 'surat-masuk';

    protected function getTableQuery(): Builder
    {

        $unitId = Auth::user()->unit_kerja_id;

        return Surat::query()
            ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                $q->where('unit_kerja_id', $unitId);
            })
            ->with([
                'unitPengirim',
                'suratUnits' => fn($q) => $q->where('unit_kerja_id', $unitId),
                'disposisis' => fn($q) => $q->where('unit_tujuan_id', $unitId),
            ])

            ->orderByDesc('created_at');
    }


    public function table(Table $table): Table
    {
        return $table
            ->poll('7s')
            ->emptyStateHeading('Tidak Ada Data Surat')
            ->emptyStateDescription('')
            ->columns([
                TextColumn::make('perihal')
                    ->label('Subject')
                    ->wrap()
                    ->searchable()
                    ->weight('bold')
                    ->description(function (Surat $record) {
                        if ($record->tipe_surat === 'EKSTERNAL') {
                            return ($record->pengirim_nama ?? 'Eksternal') . ' via ' . ($record->unitPengirim?->nama_unit ?? '-');
                        }
                        return $record->unitPengirim?->nama_unit ?? '-';
                    }),

                TextColumn::make('tipe_surat')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'INTERNAL' => 'gray',
                        'EKSTERNAL' => 'warning',
                        'PENGAJUAN' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status_surat')
                    ->label('Status')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->activeTab === 'persetujuan')
                    ->color(fn(?string $state) => match ($state) {
                        'BARU' => 'primary',
                        'DIPROSES' => 'warning',
                        'SELESAI' => 'success',
                        default => 'secondary',
                    }),

                TextColumn::make('status_baca')
                    ->label('Status Baca')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->activeTab !== 'persetujuan')
                    ->getStateUsing(fn(Surat $record) => $record->suratUnits->firstWhere('unit_kerja_id', Auth::user()->unit_kerja_id)?->status_baca)
                    ->color(fn(?string $state) => match ($state) {
                        'BELUM' => 'danger',
                        'SUDAH' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => strtoupper($state ?? '') === 'SUDAH' ? 'Sudah Dibaca' : 'Belum Dibaca'),

                TextColumn::make('status_disposisi')
                    ->label('Disposisi')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->activeTab !== 'persetujuan')
                    ->getStateUsing(fn(Surat $record) => $record->disposisis->firstWhere('unit_tujuan_id', Auth::user()->unit_kerja_id)?->status_disposisi)
                    ->color(fn(?string $state) => match ($state) {
                        'MENUNGGU' => 'warning',
                        'DITERIMA' => 'success',
                        'DITOLAK'  => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => $state ?? '-'),

                TextColumn::make('created_at')
                    ->label('Tanggal Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui / TTD')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($livewire, Surat $record) => $livewire->activeTab === 'persetujuan' && $record->status_surat === 'DIPROSES')
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

                Action::make('reject')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($livewire, Surat $record) => $livewire->activeTab === 'persetujuan' && $record->status_surat === 'DIPROSES')
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
                            isRejection: false,
                            catatan: $data['catatan']
                        );

                        \Filament\Notifications\Notification::make()->title('Permintaan revisi berhasil dikirim ke pembuat surat')->success()->send();
                    }),

                Action::make('tolak_persetujuan')
                    ->label('Tolak Persetujuan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn($livewire, Surat $record) => $livewire->activeTab === 'persetujuan' && $record->status_surat === 'DIPROSES')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan mengapa surat ini ditolak...'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Surat Pengajuan')
                    ->modalDescription('Apakah Anda yakin ingin menolak surat pengajuan ini? Surat akan dibatalkan.')
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
                            isRejection: true,
                            catatan: $data['catatan']
                        );

                        \Filament\Notifications\Notification::make()->title('Surat pengajuan berhasil ditolak dan dibatalkan')->success()->send();
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('tanggal')
                    ->schema([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                \Filament\Tables\Filters\SelectFilter::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->options([
                        'INTERNAL' => 'Internal',
                        'PENGAJUAN' => 'Pengajuan (Permohonan)',
                        'TERBITAN' => 'Terbitan (Surat Resmi)',
                        'EKSTERNAL' => 'Eksternal',
                    ]),
            ])
            ->recordUrl(
                fn(Surat $record): string => DetailSurat::getUrl(
                    parameters: ['surat' => $record->id],
                    panel: 'simas'
                )
            )
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...));
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'semua';
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->modifyQueryUsing(function (Builder $query) {
                    $unitId = Auth::user()->unit_kerja_id;
                    return $query->untukUnit($unitId);
                }),
            'langsung' => Tab::make('Langsung')
                ->modifyQueryUsing(function (Builder $query) {
                    $unitId = Auth::user()->unit_kerja_id;
                    return $query->masukLangsung($unitId);
                }),
            'disposisi' => Tab::make('Disposisi')
                ->modifyQueryUsing(function (Builder $query) {
                    $unitId = Auth::user()->unit_kerja_id;
                    return $query->disposisi($unitId);
                }),
            'persetujuan' => Tab::make('Pengajuan')
                ->modifyQueryUsing(function (Builder $query) {
                    $unitId = Auth::user()->unit_kerja_id;
                    return $query->where('status_surat', 'DIPROSES')
                                 ->whereHas('riwayats', function ($q) use ($unitId) {
                                     $q->where('status', 'MENUNGGU')
                                       ->where('unit_tujuan_id', $unitId);
                                 });
                }),
        ];
    }
}
