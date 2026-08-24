<?php

namespace App\Providers;

use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserMahasiswa;
use App\Models\UserPegawai;
use App\Policies\SuratPolicy;
use App\Policies\UnitKerjaPolicy;
use App\Policies\UserMahasiswaPolicy;
use App\Policies\UserPegawaiPolicy;
use App\Policies\UserPolicy;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        Surat::class => SuratPolicy::class,
        UserMahasiswa::class => UserMahasiswaPolicy::class,
        UserPegawai::class => UserPegawaiPolicy::class
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Model::unguard();

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                if (!auth()->check()) return '';

                $unit = auth()->user()->getActiveJabatan()?->unitKerja?->nama_unit ?? "Admin Sistem";
                $switchUrl = \App\Filament\Pages\SwitchRole::getUrl();

                // Changed from <div> to <a> and added hover effects so it feels like a real button!
                return '<a href="' . $switchUrl . '" class="flex items-center px-3 py-1.5 text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition-colors rounded-lg border border-primary-100 dark:border-primary-900 mr-4 cursor-pointer">
                            <span class="mr-2">🏢</span>' . $unit . '

                        </a>';
            }
        );
    }
}
