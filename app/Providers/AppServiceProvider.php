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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
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

        // Force the login limiter to allow unlimited attempts if in development
        RateLimiter::for('login', function (Request $request) {
            if (App::environment('local')) {
                return Limit::none(); // 🚫 Disables the limiter completely
            }

            // Keep standard safety limits for production
            return Limit::perMinute(5)->by($request->ip());
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                if (!auth()->check()) return '';

                $user = auth()->user();

                // 1. ADMIN SISTEM
                if ($user->tipe_entitas === 'ADMIN') {
                    return '<span class="flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mr-4 select-none">
                                Admin Sistem
                            </span>';
                }

                // 2. MAHASISWA
                if ($user->tipe_entitas === 'MAHASISWA') {
                    return '<span class="flex items-center px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800 mr-4 select-none">
                                Mahasiswa
                            </span>';
                }

                // 3. STAF / PEGAWAI
                $activeJabatan = $user->getActiveJabatan();
                $unitName = $activeJabatan?->unitKerja?->nama_unit;

                // Jika Staf Yatim (Stray Account tanpa unit/jabatan)
                if (!$unitName) {
                    return '<span class="flex items-center px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800 mr-4 select-none" title="Akun belum memiliki penempatan unit aktif">
                                <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                Belum Ada Unit
                            </span>';
                }

                $switchUrl = \App\Filament\Pages\SwitchRole::getUrl();

                return '<a href="' . $switchUrl . '" title="Klik untuk berganti peran/unit" class="flex items-center px-3 py-1.5 text-sm font-medium text-primary-700 hover:bg-primary-50 transition-colors rounded-lg border dark:text-primary-400 border-primary-200 dark:bg-primary-950/30 dark:border-primary-800 mr-4 cursor-pointer">

                            <span>' . e($unitName) . '</span>
                        </a>';
            }
        );
    }
}
