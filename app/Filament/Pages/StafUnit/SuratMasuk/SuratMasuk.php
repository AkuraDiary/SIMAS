<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\Surat;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuratMasuk extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.pages.staf-unit.surat-masuk.surat-masuk';
    public int $lastCount = 0;

    public function mount(): void
    {
        // for initials
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
        return Auth::user()?->peran === 'stafunit';
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
            ->untukUnit($unitId)
            ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                $q->where('unit_kerja_id', $unitId);
            })
            ->with([
                'unitPengirim',
                'suratUnits' => fn($q) => $q->where('unit_kerja_id', $unitId),
                'disposisis' => fn($q) => $q->where('unit_tujuan_id', $unitId),
            ])

            ->orderByDesc('tanggal_kirim');
    }


    public function table(Table $table): Table
    {
        return $table
            ->poll('7s')
            ->emptyStateHeading('Tidak Ada Data Surat')
            ->emptyStateDescription('')
            ->columns([

                // TextColumn::make('status_surat')
                //     ->label('Status Surat')
                //     ->searchable()
                //     ->badge()
                //     ->sortable()
                //     ->getStateUsing(function (Surat $record): string {
                //         return match ($record?->status_surat) {
                //             'TERKIRIM' => 'Surat Baru',
                //             default => $record?->status_surat,
                //         };
                //     })
                //     ->color(function (Surat $record): string {

                //         return match ($record->status_surat) {
                //             'BARU' => 'primary',
                //             'DIPROSES' => 'warning',
                //             'SELESAI' => 'success',
                //             default => 'secondary',
                //         };
                //     }),

                TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->searchable(),

                TextColumn::make('perihal')
                    ->label('Perihal')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                TextColumn::make('unitPengirim.nama_unit')
                    ->label('Pengirim')
                    ->getStateUsing(function (Surat $record): string {
                        if ($record->tipe_surat === 'EKSTERNAL') {
                            return $record->pengirim_eksternal . ' melalui ' .  $record->unitPengirim->nama_unit;
                        } else {
                            return $record->unitPengirim->nama_unit;
                        }
                    }),

                TextColumn::make('tujuan_label')
                    ->label('Tujuan')
                    ->badge()
                    ->getStateUsing(function (Surat $record): string {
                        $unitId = Auth::user()->unit_kerja_id;

                        $disposisi = $record->disposisis
                            ->firstWhere('unit_tujuan_id', $unitId);

                        if ($disposisi) {
                            $unitAsal = $disposisi->unitPembuat?->nama_unit;
                            return $unitAsal
                                ? 'Disposisi dari ' . $unitAsal
                                : 'Disposisi';
                        }

                        $suratUnit = $record->suratUnits->first();

                        return match ($suratUnit?->jenis_tujuan) {
                            'utama' => 'Tujuan Utama',
                            'tembusan' => 'Tembusan',
                            default => '-',
                        };
                    })
                    ->color(function (Surat $record): string {
                        $unitId = Auth::user()->unit_kerja_id;

                        if ($record->disposisis->contains('unit_tujuan_id', $unitId)) {
                            return 'warning';
                        }

                        return match ($record->suratUnits->first()?->jenis_tujuan) {
                            'utama' => 'primary',
                            'tembusan' => 'gray',
                            default => 'secondary',
                        };
                    }),


                TextColumn::make('status_baca')
                    ->label('Status Baca')
                    ->badge()
                    ->getStateUsing(function (Surat $record) {
                        return $record->suratUnits->first()?->status_baca;
                    })
                    ->hidden(
                        false
                    )
                    ->color(fn(?string $state) => match ($state) {
                        'BELUM' => 'danger',
                        'SUDAH' => 'success',
                        default => 'gray',
                    })->formatStateUsing(
                        fn(string $state): string =>
                        strtoupper($state) === 'SUDAH'
                            ? 'Sudah Dibaca'
                            : 'Belum Dibaca'
                    ),

                TextColumn::make('status_disposisi')
                    ->label('Disposisi')
                    ->badge()
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;

                        return $record->disposisis
                            ->firstWhere('unit_tujuan_id', $unitId)
                            ?->status_disposisi;
                    })

                    ->color(fn(?string $state) => match ($state) {
                        'BARU' => 'danger',
                        'DIPROSES' => 'warning',
                        'SELESAI' => 'success',
                        default => null,
                    }),


            ])
            ->filters([

                SelectFilter::make('jenis_masuk')
                    ->options([
                        'langsung' => 'Surat Langsung',
                        'disposisi' => 'Disposisi',
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        // Access the selected value via the 'value' key
                        $selectedValue = $data['value'] ?? null;

                        // If nothing is selected
                        if (blank($selectedValue)) {
                            return $query;
                        }
                        $unitId = Auth::user()->unit_kerja_id;

                        match ($selectedValue) {
                            'langsung'  => $query->masukLangsung($unitId),
                            'disposisi' => $query->disposisi($unitId),
                            default     => null,
                        };

                        $query->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                            $q->where('unit_kerja_id', $unitId);
                        });
                        return $query;
                    })
            ])
            ->recordUrl(
                fn(Surat $record): string => DetailSurat::getUrl(
                    parameters: ['surat' => $record->id],
                    panel: 'simas'
                )
            );
    }
}
