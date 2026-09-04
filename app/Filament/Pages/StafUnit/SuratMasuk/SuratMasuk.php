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

        return app(\App\Services\UnitAksesService::class)
            ->applySuratMasukFilter(Surat::query(), Auth::user(), $unitId)
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

        return app(\App\Services\UnitAksesService::class)
            ->applySuratMasukFilter(Surat::query(), Auth::user(), $unitId)
            ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                $q->where('unit_kerja_id', $unitId);
            })
            ->with([
                'unitPengirim',
                'suratUnits' => fn($q) => $q->where('unit_kerja_id', $unitId),
                'disposisis' => fn($q) => $q->where('unit_tujuan_id', $unitId),
                'riwayats' => fn($q) => $q->where('unit_tujuan_id', $unitId),
            ]);
        // ->orderByDesc('created_at');
    }


    public function table(Table $table): Table
    {
        return $table
            ->poll('7s')
            ->emptyStateHeading('Tidak Ada Data Surat')
            ->emptyStateDescription('')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('perihal')
                    ->label('Perihal & Pengirim')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('perihal', 'like', "%{$search}%")
                            ->orWhere('pengirim_nama', 'like', "%{$search}%")
                            ->orWhereHas('userPegawaiJabatan.pegawai', function ($q) use ($search) {
                                $q->where('nama_lengkap', 'like', "%{$search}%");
                            })
                            ->orWhereHas('unitPengirim', function ($q) use ($search) {
                                $q->where('nama_unit', 'like', "%{$search}%");
                            });
                    })
                    ->weight('bold')
                    ->wrap()
                    ->description(function (Surat $record) {
                        $nomor = $record->nomor_surat ? $record->nomor_surat . ' • ' : '';
                        $pengirim = $record->tipe_surat === 'EKSTERNAL'
                            ? ($record->pengirim_nama ?? 'Eksternal') . ' via ' . ($record->unitPengirim?->nama_unit ?? '-')
                            : ($record->userPegawaiJabatan->pegawai->nama_lengkap ?? '-') . ' - ' . ($record->unitPengirim?->nama_unit ?? '-');
                        return $nomor . $pengirim;
                    }),

                TextColumn::make('tipe_surat')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'INTERNAL' => 'gray',
                        'EKSTERNAL' => 'warning',
                        'PENGAJUAN' => 'info',
                        default => 'gray',
                    }),


                TextColumn::make('status_surat')
                    ->label('Status Surat')
                    ->searchable()
                    ->badge()
                    ->getStateUsing(function (Surat $record, $livewire) {
                        // if ($record->tipe_surat ==='PENGAJUAN') {
                        //     return 'PENGAJUAN: ' . $record->status_surat;
                        // }

                        $disposisi = $record->disposisis->firstWhere('unit_tujuan_id', Auth::user()->unit_kerja_id)?->status_disposisi;
                        if ($disposisi) return 'DISPOSISI: ' . strtoupper($disposisi);

                        else return $record->status_surat;
                    })
                    ->color(fn(?string $state) => match (true) {
                        $state === 'SELESAI' => 'success',
                        $state === 'DIPROSES' => 'warning',
                        $state === 'REVISI' => 'danger',
                        $state === 'DITOLAK' => 'danger',
                        str_contains($state ?? '', 'DISPOSISI: MENUNGGU') => 'warning',
                        str_contains($state ?? '', 'DISPOSISI: DIPROSES') => 'primary',
                        str_contains($state ?? '', 'DISPOSISI: SELESAI') => 'success',
                        str_contains($state ?? '', 'PENGAJUAN: DIPROSES') => 'warning',
                        str_contains($state ?? '', 'PENGAJUAN: SELESAI') => 'success',
                        default => 'gray',
                    }),



                TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('status baca')
                    ->label('Status Baca')
                    ->badge()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // sort via sub-query pivot table
                        $unitId = Auth::user()->unit_kerja_id;
                        return $query->orderBy(
                            \App\Models\SuratUnit::select('status_baca')
                                ->whereColumn('surat_unit.surat_id', 'surats.id')
                                ->where('unit_kerja_id', $unitId)
                                ->take(1),
                            $direction
                        );
                    })

                    ->getStateUsing(function (Surat $record, $livewire) {
                        $baca = $record->suratUnits->firstWhere('unit_kerja_id', Auth::user()->unit_kerja_id)?->status_baca;

                        // If there is no SuratUnit record, it means it's pure disposisi
                        if (!$baca) return null;

                        return $baca === 'SUDAH' ? 'DIBACA' : 'BARU';
                    })
                    ->color(fn(?string $state) => match (true) {
                        $state === 'BARU' => 'danger',
                        $state === 'DIBACA' => 'success',
                        default => 'gray',
                    }),

            ])
            ->recordActions([])
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                \Filament\Tables\Filters\SelectFilter::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->options([
                        'INTERNAL' => 'Internal',
                        'PENGAJUAN' => 'Pengajuan',
                        'TERBITAN' => 'Terbitan (Surat Resmi)',
                        'EKSTERNAL' => 'Eksternal',
                    ]),
            ])
            ->recordUrl(function (Surat $record) {
                $isPersetujuan = $record->riwayats->isNotEmpty();

                return DetailSurat::getUrl(
                    parameters: [
                        'surat' => $record,
                        'scope' => $isPersetujuan ? 'persetujuan' : 'masuk'
                    ],
                    panel: 'simas'
                );
            })
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
                    // untukUnit is already applied in getTableQuery
                    return $query;
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
                    return $query->where('tipe_surat', 'PENGAJUAN');
                    // ->whereHas('riwayats', function ($q) use ($unitId) {
                    //     $q->where('status', 'MENUNGGU')
                    //         ->where('unit_tujuan_id', $unitId);
                    // });
                    // return $query->where('status_surat', 'DIPROSES')
                    //     ->whereHas('riwayats', function ($q) use ($unitId) {
                    //         $q->where('status', 'MENUNGGU')
                    //             ->where('unit_tujuan_id', $unitId);
                    //     });
                }),
        ];
    }
}
