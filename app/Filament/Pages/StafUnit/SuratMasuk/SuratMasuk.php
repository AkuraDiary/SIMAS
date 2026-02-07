<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\Surat;
use App\Models\SuratUnit;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuratMasuk extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.pages.staf-unit.surat-masuk.surat-masuk';

    public static function canAccess(): bool
    {
        return Auth::user()?->peran === 'stafunit';
    }

    protected static ?string $navigationLabel = 'Surat Masuk';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Inbox;
    protected static ?string $slug = 'surat-masuk';


    protected function getTableQuery(): Builder
    {
        $unitId = Auth::user()->unit_kerja_id;

        return SuratUnit::query()
            ->where('unit_kerja_id', $unitId)
            ->with([
                'surat.unitPengirim',
            ])
            ->orderByDesc('tanggal_terima');
    }


    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('surat.nomor_surat')
                ->label('Nomor Surat')
                ->searchable(),

            TextColumn::make('surat.perihal')
                ->label('Perihal')
                ->wrap(),

            TextColumn::make('surat.unitPengirim.nama_unit')
                ->label('Pengirim'),

            TextColumn::make('jenis_tujuan')
                ->label('Tujuan')
                ->badge(),

            TextColumn::make('status_baca')
                ->label('Status Baca')
                ->badge(),

            TextColumn::make('tanggal_terima')
                ->label('Diterima')
                ->date(),

        ])
            ->recordUrl(
                fn(SuratUnit $record): string => DetailSurat::getUrl(
                    parameters: ['surat_unit_id' => $record->id],
                    panel: 'simas'
                )
            );
    }
    
}
