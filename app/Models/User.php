<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Surat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'email',
        'peran',
        'status_user',
        'unit_kerja_id',
    ];


    public function canAccessPanel(Panel $panel): bool
    {
        // Rule 1: Must be active
        // Rule 2: Must be either SuperAdmin or StafUnit
        // Rule 3: Unit Kerja must be active if unit kerja not null
        if ($this->status_user !== 'aktif') {
            return false;
        }

        if (!in_array($this->peran, ['superadmin', 'stafunit'])) {
            return false;
        }

        if ($this->unitKerja && $this->unitKerja->status_unit !== 'aktif') {
            return false;
        }

        if($this->peran === 'stafunit' && !$this->unitKerja){
            return false;
        }

        return true;
    }

    public function suratDibuat(): HasMany
    {
        return $this->hasMany(Surat::class, 'user_pembuat_id');
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->nama_lengkap;
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }
}
