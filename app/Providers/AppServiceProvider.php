<?php

namespace App\Providers;

use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;
use App\Policies\SuratPolicy;
use App\Policies\UnitKerjaPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

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
        User::class => UserPolicy::class,
        UnitKerja::class => UnitKerjaPolicy::class,
        Surat::class => SuratPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Model::unguard();
    }
}
