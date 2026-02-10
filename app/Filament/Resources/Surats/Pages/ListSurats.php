<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Request;

class ListSurats extends ListRecords
{
    protected static string $resource = SuratResource::class;

     public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => Request::query('scope')]) => $this->getTitle(),
            '#' => 'List Surat',
        ];
    }
    public function getTitle(): string
    {
        return match (Request::query('scope')) {
            'keluar' => 'Surat Keluar',
            'draft'  => 'Draft Surat',
            'arsip'  => 'Arsip Surat',
            default  => 'Semua Surat',
        };
    }

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label("Buat Surat Baru"),
        ];
    }
}
