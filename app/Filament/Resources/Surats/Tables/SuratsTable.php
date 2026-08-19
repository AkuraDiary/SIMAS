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
        $this->scope = request()->get('scope', 'masuk');
    }


    public static function configure(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('perihal')
                    ->label(fn($livewire) => ($livewire->scope ?? request('scope')) === 'draft' ? 'Subject' : 'Subject')
                    ->searchable()
                    ->weight('bold')
                    ->description(function (Surat $record, $livewire) {
                        $scope = $livewire->scope ?? request('scope');
                        if ($scope === 'draft') {
                            return $record->nomor_surat ?? 'DRAFT-' . date('Y-m-') . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                        }
                        return ($record->userPegawaiJabatan->pegawai->nama_lengkap ?? '') . ' - ' . ($record->unitPengirim?->nama_unit ?? '');
                    }),

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

                TextColumn::make('arsip_kategori')
                    ->label('Category')
                    ->badge()
                    ->visible(fn($livewire) => ($livewire->scope ?? request('scope')) === 'arsip')
                    ->getStateUsing(function (Surat $record) {
                        $unitId = Auth::user()->unit_kerja_id;
                        $arsip = $record->arsipSurats->firstWhere('unit_kerja_id', $unitId);
                        return $arsip?->kategoriArsip?->nama ?? '-';
                    })
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(fn($livewire) => match ($livewire->scope ?? request('scope')) {
                        'arsip' => 'Diarsipkan',
                        default => 'Terakhir Update'
                    })
                    ->dateTime('d M Y, H:i')
                    ->sortable(),


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
            ->recordUrl(function (Surat $record) {
                if (in_array($record->status_surat, ['DRAFT', 'REVISI'])) {
                    return EditSurat::getUrl(['record' => $record->id]);
                }

                $unitId = \Illuminate\Support\Facades\Auth::user()->unit_kerja_id;
                $isPersetujuan = $record->riwayats()
                    ->where('status', 'MENUNGGU')
                    ->where('unit_tujuan_id', $unitId)
                    ->exists();

                return DetailSurat::getUrl(
                    parameters: [
                        'surat' => $record->id,
                        'scope' => $isPersetujuan ? 'persetujuan' : (request('scope') ?? 'masuk'),
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
