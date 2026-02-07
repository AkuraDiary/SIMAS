<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Panel;
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
    ];

    // This is the "Rule Enforcer"
    public function canAccessPanel(Panel $panel): bool
    {
        // dd($this->status_user==='aktif');
        return true;
        // return true;
        // Rule 1: Must be active
        // Rule 2: Must be either SuperAdmin or StafUnit
        return $this->status_user === 'aktif' &&
        in_array($this->peran, ['superadmin', 'stafunit']);
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
}
