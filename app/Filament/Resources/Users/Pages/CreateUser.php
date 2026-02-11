<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // redirect to list page
        $this->redirect(UserResource::getUrl());
    }

    public static function getTabs(): array
    {
        $unitId = Auth::user()->unit_kerja_id;

        return [
            'draft' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status_surat', 'DRAFT')
                ),

            'keluar' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query
                        ->where('status_surat', '!=', 'DRAFT')
                        ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                            $q->where('unit_kerja_id', $unitId);
                        })
                ),

            'arsip' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query
                        ->whereHas(
                            'arsipSurats',
                            fn($q) =>
                            $q->where('unit_kerja_id', $unitId)
                        )
                        ->with('arsipSurats.kategoriArsip')
                ),
        ];
    }
}
