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
            // ->recordActions([
            //     EditAction::make()->visible(fn($record) => in_array($record->status_surat, ['DRAFT', 'REVISI'])),
            //     DeleteAction::make()->visible(fn($record) => in_array($record->status_surat, ['DRAFT', 'REVISI'])),

            //     \Filament\Actions\Action::make('ajukan')
            //         ->label('Ajukan/Kirim Surat')
            //         ->icon('heroicon-o-paper-airplane')
            //         ->color('primary')
            //         ->visible(fn(Surat $record) => in_array($record->status_surat, ['DRAFT', 'REVISI']))
            //         ->form([
            //             \Filament\Forms\Components\Select::make('unit_persetujuan_id')
            //                 ->label('Tujuan Persetujuan / Atasan')
            //                 ->options(function () {
            //                     return \App\Models\UnitKerja::query()
            //                         ->where('id', '<>', Auth::user()->unit_kerja_id)
            //                         ->pluck('nama_unit', 'id');
            //                 })
            //                 ->searchable()
            //                 ->helperText('Pilih unit atasan untuk persetujuan. Kosongkan jika ingin langsung mengirim surat ke penerima.')
            //                 ->nullable(),
            //             \Filament\Forms\Components\Textarea::make('catatan')
            //                 ->label('Catatan (Opsional)')
            //                 ->placeholder('Tambahkan catatan...')
            //                 ->nullable(),
            //         ])
            //         ->modalHeading('Ajukan atau Kirim Surat')
            //         ->modalDescription('Apakah Anda yakin? Surat tidak dapat diedit lagi setelah dikirim atau diajukan.')
            //         ->action(function (Surat $record, array $data): void {
            //             $unitTujuan = $record->unitTujuan()->first();
            //             if (!$unitTujuan) {
            //                 \Filament\Notifications\Notification::make()->title('Gagal: Surat belum memiliki unit tujuan. Edit surat terlebih dahulu.')->danger()->send();
            //                 return;
            //             }

            //             if (!empty($data['unit_persetujuan_id'])) {
            //                 app(\App\Services\SuratRoutingService::class)->submitForApproval(
            //                     surat: $record,
            //                     unitTujuanId: $data['unit_persetujuan_id'],
            //                     catatan: $data['catatan'] ?? 'Pengajuan surat'
            //                 );
            //                 \Filament\Notifications\Notification::make()->title('Surat berhasil diajukan untuk persetujuan')->success()->send();
            //             } else {
            //                 $formatGlobal = \App\Models\FormatNomorSurat::whereNull('unit_kerja_id')->where('is_active', true)->first();
            //                 if ($formatGlobal && empty($record->nomor_surat)) {
            //                     $record->nomor_surat = $formatGlobal->generateNomorSurat($record);
            //                 }

            //                 $newStatus = ($record->tipe_surat === 'PENGAJUAN') ? 'TERBIT' : 'SELESAI';
            //                 $record->status_surat = $newStatus;
            //                 $record->tanggal_kirim = now();
            //                 $record->save();

            //                 \Filament\Notifications\Notification::make()->title('Surat berhasil dikirim langsung ke tujuan')->success()->send();
            //             }
            //         }),

            //     \Filament\Actions\Action::make('approve')
            //         ->label('Setujui / TTD')
            //         ->icon('heroicon-o-check-circle')
            //         ->color('success')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'persetujuan' && $record->status_surat === 'DIPROSES')
            //         ->schema([
            //             \Filament\Forms\Components\Textarea::make('catatan')
            //                 ->label('Catatan Persetujuan (Opsional)')
            //                 ->placeholder('Catatan atau catatan persetujuan...'),
            //         ])
            //         ->action(function (Surat $record, array $data): void {
            //             $activeRiwayat = $record->riwayats()
            //                 ->where('status', 'MENUNGGU')
            //                 ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
            //                 ->latest()
            //                 ->first();

            //             if (!$activeRiwayat) {
            //                 \Filament\Notifications\Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
            //                 return;
            //             }

            //             app(\App\Services\SuratRoutingService::class)->approveStep(
            //                 currentRiwayat: $activeRiwayat,
            //                 actor: Auth::user(),
            //                 isFinalStep: true,
            //                 isSignatureRequired: true,
            //                 catatan: $data['catatan'] ?? null
            //             );

            //             \Filament\Notifications\Notification::make()->title('Surat berhasil disetujui & ditandatangani')->success()->send();
            //         }),

            //     \Filament\Actions\Action::make('reject')
            //         ->label('Minta Revisi')
            //         ->icon('heroicon-o-x-circle')
            //         ->color('danger')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'persetujuan' && $record->status_surat === 'DIPROSES')
            //         ->form([
            //             \Filament\Forms\Components\Textarea::make('catatan')
            //                 ->label('Alasan Revisi')
            //                 ->required()
            //                 ->placeholder('Jelaskan bagian yang perlu diperbaiki...'),
            //         ])
            //         ->action(function (Surat $record, array $data): void {
            //             $activeRiwayat = $record->riwayats()
            //                 ->where('status', 'MENUNGGU')
            //                 ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
            //                 ->latest()
            //                 ->first();

            //             if (!$activeRiwayat) {
            //                 \Filament\Notifications\Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
            //                 return;
            //             }

            //             app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
            //                 currentRiwayat: $activeRiwayat,
            //                 actor: Auth::user(),
            //                 newStatus: 'REVISI',
            //                 catatan: $data['catatan']
            //             );

            //             \Filament\Notifications\Notification::make()->title('Surat dikembalikan untuk revisi')->warning()->send();
            //         }),

            //     \Filament\Actions\Action::make('tolak_persetujuan')
            //         ->label('Tolak Persetujuan')
            //         ->icon('heroicon-o-archive-box-x-mark')
            //         ->color('danger')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'persetujuan' && $record->status_surat === 'DIPROSES')
            //         ->form([
            //             \Filament\Forms\Components\Textarea::make('catatan')
            //                 ->label('Alasan Penolakan')
            //                 ->required()
            //                 ->placeholder('Jelaskan alasan penolakan...'),
            //         ])
            //         ->action(function (Surat $record, array $data): void {
            //             $activeRiwayat = $record->riwayats()
            //                 ->where('status', 'MENUNGGU')
            //                 ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
            //                 ->latest()
            //                 ->first();

            //             if (!$activeRiwayat) {
            //                 \Filament\Notifications\Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
            //                 return;
            //             }

            //             app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
            //                 currentRiwayat: $activeRiwayat,
            //                 actor: Auth::user(),
            //                 newStatus: 'DITOLAK',
            //                 catatan: $data['catatan']
            //             );

            //             \Filament\Notifications\Notification::make()->title('Surat ditolak sepenuhnya')->danger()->send();
            //         }),

            //     \Filament\Actions\Action::make('terima_pengajuan')
            //         ->label('Terima Pengajuan')
            //         ->icon('heroicon-o-check-circle')
            //         ->color('success')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'pengajuan' && $record->status_surat === 'DIPROSES')
            //         ->action(function (Surat $record): void {
            //             $record->status_surat = 'SELESAI'; // Or some other accepted status
            //             $record->save();
            //             \Filament\Notifications\Notification::make()->title('Pengajuan Diterima')->success()->send();
            //         }),

            //     \Filament\Actions\Action::make('tolak_pengajuan')
            //         ->label('Tolak Pengajuan')
            //         ->icon('heroicon-o-x-circle')
            //         ->color('danger')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'pengajuan' && in_array($record->status_surat, ['DIPROSES', 'SELESAI']))
            //         ->form([
            //             \Filament\Forms\Components\Textarea::make('alasan')
            //                 ->label('Alasan Penolakan')
            //                 ->required(),
            //         ])
            //         ->action(function (Surat $record, array $data): void {
            //             $record->status_surat = 'DITOLAK';
            //             // Save reason? Maybe in a note or just change status for now
            //             $record->save();
            //             \Filament\Notifications\Notification::make()->title('Pengajuan Ditolak')->danger()->send();
            //         }),

            //     \Filament\Actions\Action::make('buat_terbitan')
            //         ->label('Terbitkan Surat Balasan')
            //         ->icon('heroicon-o-document-plus')
            //         ->color('primary')
            //         ->visible(fn($livewire, Surat $record) => ($livewire->scope ?? request('scope')) === 'pengajuan' && in_array($record->status_surat, ['DIPROSES', 'SELESAI', 'TERBIT']))
            //         ->url(fn(Surat $record) => CreateSurat::getUrl(['terbitan_for_surat_id' => $record->id, 'tipe_surat' => 'TERBITAN']))
            //         ->openUrlInNewTab(),
            // ])
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
