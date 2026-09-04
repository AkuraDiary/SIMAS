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
use App\Filament\Resources\Surats\Pages\CreateSurat;
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
        $this->scope = request()->input('scope', 'masuk');
    }


    public static function configure(Table $table): Table
    {

        return $table
            // ->poll('7s')
            ->columns([
                TextColumn::make('perihal')
                    ->label(fn($livewire) => ($livewire->scope ?? request('scope')) === 'draft' ? 'Subject' : 'Subject')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('perihal', 'like', "%{$search}%")
                                ->orWhere('pengirim_nama', 'like', "%{$search}%")
                                ->orWhereHas('userPegawaiJabatan.pegawai', function ($sub) use ($search) {
                                    $sub->where('nama_lengkap', 'like', "%{$search}%");
                                })
                                ->orWhereHas('unitPengirim', function ($sub) use ($search) {
                                    $sub->where('nama_unit', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->weight('bold')
                    ->description(function (Surat $record, $livewire) {
                        $scope = $livewire->scope ?? request('scope');
                        if ($scope === 'draft') {
                            return $record->nomorSuratLogs->last()?->nomor_lengkap ?? 'DRAFT-' . date('Y-m-') . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                        }
                        return ($record->userPegawaiJabatan->pegawai->nama_lengkap ?? '') . ' - ' . ($record->unitPengirim?->nama_unit ?? '');
                    })
                    ->weight('bold')
                    ->description(function (Surat $record, $livewire) {
                        $scope = $livewire->scope ?? request('scope');
                        if ($scope === 'draft') {
                            return $record->nomorSuratLogs->last()?->nomor_lengkap ?? 'DRAFT-' . date('Y-m-') . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                        }
                        return ($record->userPegawaiJabatan->pegawai->nama_lengkap ?? '') . ' - ' . ($record->unitPengirim?->nama_unit ?? '');
                    }),

                TextColumn::make('nomorSuratLogs.nomor_lengkap')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) !== 'draft')
                    ->getStateUsing(fn(Surat $record) => $record->nomorSuratLogs->last()?->nomor_lengkap ?? '-'),

                TextColumn::make('pembuat.name')
                    ->label('Dibuat Oleh')
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'draft')
                    ->getStateUsing(fn(Surat $record) => $record->userPegawaiJabatan->pegawai->nama_lengkap  ?? '-'),

                TextColumn::make('status_surat')
                    ->label('Status')
                    ->badge()
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
                    })
                    ->visible(fn($livewire) => !in_array($livewire->scope ?? request('scope'), ['arsip', 'draft'])),

                TextColumn::make('tipe_surat')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'INTERNAL' => 'gray',
                        'EKSTERNAL' => 'warning',
                        'PENGAJUAN' => 'info',
                        'TERBITAN' => 'success',
                        default => 'gray',
                    })
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip'),

                TextColumn::make('arsip_kategori')
                    ->label('Kategori Arsip')
                    ->badge()
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $unitId = Auth::user()->unit_kerja_id;
                        return $query->whereHas('arsipSurats', function ($q) use ($search, $unitId) {
                            $q->where('unit_kerja_id', $unitId)
                                ->whereHas('kategoriArsip', function ($kq) use ($search) {
                                    $kq->where('nama', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;
                        $arsip = $record->arsipSurats->firstWhere('unit_kerja_id', $unitId);
                        return $arsip?->kategoriArsip?->nama ?? '-';
                    })
                    ->color('info'),

                TextColumn::make('tanggal_arsip')
                    ->label('Tanggal Arsip')
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip')
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;
                        $arsip = $record->arsipSurats->firstWhere('unit_kerja_id', $unitId);
                        return $arsip?->tanggal_arsip ? \Carbon\Carbon::parse($arsip->tanggal_arsip)->format('d M Y, H:i') : '-';
                    }),

                TextColumn::make('catatan_arsip')
                    ->label('Catatan Arsip')
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $unitId = Auth::user()->unit_kerja_id;
                        return $query->whereHas('arsipSurats', function ($q) use ($search, $unitId) {
                            $q->where('unit_kerja_id', $unitId)
                                ->where('catatan', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;
                        $arsip = $record->arsipSurats->firstWhere('unit_kerja_id', $unitId);
                        return $arsip?->catatan ?? '-';
                    })
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) !== 'arsip'),

                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) !== 'arsip'),

            ])
            ->filters([

                SelectFilter::make('jenis_surat_keluar')
                    ->label('Jenis Keluar')
                    ->options([
                        'surat' => 'Surat Keluar Utama',
                        'disposisi' => 'Disposisi Keluar',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'surat') {
                            return $query->where('unit_pengirim_id', Auth::user()->unit_kerja_id);
                        } elseif ($value === 'disposisi') {
                            return $query->whereHas('disposisis', function ($q) {
                                $unitId = Auth::user()->unit_kerja_id;
                                $userIds = \App\Models\User::ofUnitKerja($unitId)->pluck('id');
                                $q->whereIn('user_pembuat_id', $userIds);
                            });
                        }
                        return $query;
                    })
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'keluar'),

                SelectFilter::make('kategori_arsip_id')
                    ->label('Kategori Arsip')
                    ->options(function () {
                        return KategoriArsip::query()
                            ->where('unit_kerja_id', Auth::user()->unit_kerja_id)
                            ->pluck('nama', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (blank($value)) {
                            return $query;
                        }

                        $unitId = Auth::user()->unit_kerja_id;
                        return $query->whereHas('arsipSurats', function ($q) use ($value, $unitId) {
                            $q->where('kategori_arsip_id', $value)->where('unit_kerja_id', $unitId);
                        });
                    })
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip'),

                SelectFilter::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->options([
                        'INTERNAL' => 'Internal',
                        'PENGAJUAN' => 'Pengajuan',
                        'TERBITAN' => 'Terbitan',
                        'EKSTERNAL' => 'Eksternal',
                    ])
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip'),

                \Filament\Tables\Filters\Filter::make('tanggal_arsip')
                    ->label('Rentang Tanggal Arsip')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal Arsip'),
                        \Filament\Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal Arsip'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $unitId = Auth::user()->unit_kerja_id;
                        return $query
                            ->when(
                                $data['dari_tanggal'] ?? null,
                                fn(Builder $q, $date) => $q->whereHas('arsipSurats', fn($aq) => $aq->where('unit_kerja_id', $unitId)->whereDate('tanggal_arsip', '>=', $date))
                            )
                            ->when(
                                $data['sampai_tanggal'] ?? null,
                                fn(Builder $q, $date) => $q->whereHas('arsipSurats', fn($aq) => $aq->where('unit_kerja_id', $unitId)->whereDate('tanggal_arsip', '<=', $date))
                            );
                    })
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip'),


            ])
            ->recordUrl(function (Surat $record) {
                if (in_array($record->status_surat, ['DRAFT'])) {
                    return EditSurat::getUrl(['record' => $record]);
                }

                $scope = request('scope');
                if ($scope === 'arsip') {
                    return DetailSurat::getUrl(
                        parameters: [
                            'record' => $record,
                            'surat' => $record,
                            'scope' => 'arsip',
                        ],
                        panel: 'simas'
                    );
                }

                $unitId = \Illuminate\Support\Facades\Auth::user()->unit_kerja_id;
                $isPersetujuan = $record->riwayats()
                    ->where('status', 'MENUNGGU')
                    ->where('unit_tujuan_id', $unitId)
                    ->exists();

                return DetailSurat::getUrl(
                    parameters: [
                        'record' => $record,
                        'surat' => $record,
                        'scope' => $isPersetujuan ? 'persetujuan' : ($scope ?? 'masuk'),
                    ],
                    panel: 'simas'
                );
            })

            ->toolbarActions([])
            ->emptyStateHeading('TIdak Ada Data Surat')
            ->emptyStateDescription('')
            ->persistSearchInSession()
            ->persistSortInSession();;
    }
}
