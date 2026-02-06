<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
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
        // Rule 1: Must be active
        // Rule 2: Must be either SuperAdmin or StafUnit
        return $this->statusUser === 'aktif' &&
            in_array($this->peran, ['SuperAdmin', 'StafUnit']);
    }

    // Tell Laravel to use 'username' for authentication instead of 'email'
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function canAccessFilament(): bool
    {
        return true;
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
}
