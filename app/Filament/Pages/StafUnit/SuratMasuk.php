<?php

namespace App\Filament\Pages\StafUnit;

use App\Models\Surat;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class SuratMasuk extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.pages.staf-unit.surat-masuk';

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

        return Surat::query()
            ->where(function (Builder $query) use ($unitId) {
                $query
                    ->whereHas('unitTujuan', function (Builder $q) use ($unitId) {
                        $q->where('unit_kerja_id', $unitId);
                    })
                    ->orWhereHas('disposisis', function (Builder $q) use ($unitId) {
                        $q->where('unit_tujuan_id', $unitId);
                    });
            })
            ->with([
                'unitPengirim',
            ])
            ->orderByDesc('tanggal_kirim');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nomor_surat')
                ->label('Nomor Surat')
                ->searchable(),

            TextColumn::make('perihal')
                ->label('Perihal')
                ->wrap(),

            TextColumn::make('unitPengirim.nama_unit')
                ->label('Pengirim'),

        ];
    }
}
