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
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
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
        
        UnitKerja::class => UnitKerjaPolicy::class,
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
    }
}
