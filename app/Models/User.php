<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Models\Surat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

    use HasFactory, Notifiable, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'phone',
        'tipe_entitas',
        'is_active',
    ];

    public function getFilamentName(): string
    {
        $nama = $this->nama_lengkap ?? $this->username;

        return $nama;
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function suratDibuat(): HasMany
    {
        return $this->hasMany(Surat::class, 'user_pembuat_id');
    }

    /**
     * Identity record for STAF users. Holds nip/nama_lengkap.
     */
    public function pegawai(): HasOne
    {
        return $this->hasOne(UserPegawai::class);
    }

    /**
     * Identity record for MAHASISWA users. Holds nim/nama_lengkap/prodi/fakultas.
     */
    public function mahasiswa(): HasOne
    {
        return $this->hasOne(UserMahasiswa::class);
    }


    /**
     * Currently-active unit assignment for a staff user (via user_pegawai -> user_pegawai_jabatans).
     * A pegawai could technically hold more than one active jabatan; this resolves to one row
     * for the common single-unit-per-staff case the rest of the app assumes.
     */
    public function jabatanAktif(): HasOneThrough
    {
        return $this->hasOneThrough(
            UserPegawaiJabatan::class,
            UserPegawai::class,
            'user_id',          // FK on user_pegawai referencing users.id
            'user_pegawai_id',  // FK on user_pegawai_jabatans referencing user_pegawai.id
            'id',               // local key on users
            'id'                // local key on user_pegawai
        )->where('status_jabatan', 'AKTIF');
    }

    /**
     * Dapatkan UserPegawaiJabatan yang sedang aktif berdasarkan Session.
     */
    public function getActiveJabatan()
    {
        $sessionJabatanId = session('active_jabatan_id');

        if ($sessionJabatanId) {
            return UserPegawaiJabatan::find($sessionJabatanId);
        }

        // Jika belum ada di session, ambil jabatan pertama dari relasi yang sudah ada
        $firstJabatan = $this->jabatanAktif;

        if ($firstJabatan) {
            session(['active_jabatan_id' => $firstJabatan->id]);
        }

        return $firstJabatan;
    }

    /**
     * Dapatkan Unit ID dari jabatan yang sedang aktif.
     */
    public function getActiveUnitId()
    {
        return $this->getActiveJabatan()?->unit_kerja_id;
    }

    /**
     * Computed convenience accessor (UPGRADED FOR MULTI JABATAN). Resolves to the same value as before the refactor
     * (users.unit_kerja_id), now derived through the active jabatan assignment.
     * Accessible as both $user->unit_kerja_id and $user->unitKerjaId.
     */
    protected function unitKerjaId(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getActiveUnitId(),
        );
    }

    /**
     * Computed convenience accessor  (UPGRADED FOR MULTI JABATAN) returning the UnitKerja model itself.
     * Accessible as both $user->unit_kerja and $user->unitKerja (legacy call sites use the latter).
     * NOTE: not a real Eloquent relation, so it can't be used with Filament's ->relationship().
     */
    protected function unitKerja(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getActiveJabatan()?->unitKerja,
        );
    }

    /**
     * Computed convenience accessor for display name, sourced from pegawai or mahasiswa.
     * Accessible as both $user->nama_lengkap and $user->namaLengkap.
     */
    protected function namaLengkap(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->pegawai?->nama_lengkap ?? $this->mahasiswa?->nama_lengkap,
        );
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
     * Appended so Filament's default form-fill (which reads from the model's
     * array representation) picks up these computed accessors, e.g. on the
     * profile edit page. Only scalar accessors are appended to keep
     * serialization cheap and predictable.
     *
     * @var list<string>
     */
    protected $appends = [
        'nama_lengkap',
        'unit_kerja_id',
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
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }


    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * Scope to find all users that have an active jabatan in a specific unit.
     */
    public function scopeOfUnitKerja($query, $unitId)
    {
        return $query->whereHas('pegawai.jabatans', function ($q) use ($unitId) {
            $q->where('unit_kerja_id', $unitId)->where('status_jabatan', 'AKTIF');
        });
    }

    /**
     * Determine whether the user is the Kepala Unit (level_jabatan == 1).
     * Admin also returns true.
     */
    public function isKepalaUnit(?int $unitId = null): bool
    {
        if ($this->tipe_entitas === 'ADMIN') {
            return true;
        }

        if ($this->tipe_entitas !== 'STAF') {
            return false;
        }

        $jabatan = null;
        if ($unitId !== null) {
            $jabatan = $this->pegawai?->jabatanAktif()
                ->where('unit_kerja_id', $unitId)
                ->first();
        }

        $activeJabatan = $jabatan ?? $this->getActiveJabatan();
        if (!$activeJabatan || !$activeJabatan->jabatan) {
            return false;
        }

        if ($unitId !== null && (int) $activeJabatan->unit_kerja_id !== (int) $unitId) {
            return false;
        }

        return (int) $activeJabatan->jabatan->level_jabatan === 1;
    }

    /**
     * Determine whether the user can view all surat masuk in their unit.
     */
    public function canViewAllSuratMasukUnit(?int $unitId = null): bool
    {
        if ($this->isKepalaUnit($unitId)) {
            return true;
        }

        $jabatan = null;
        if ($unitId !== null) {
            $jabatan = $this->pegawai?->jabatanAktif()
                ->where('unit_kerja_id', $unitId)
                ->first();
        }

        $activeJabatan = $jabatan ?? $this->getActiveJabatan();
        if (!$activeJabatan) {
            return false;
        }

        // Check staff specific override
        if ($activeJabatan->akses_surat_masuk === 'SEMUA') {
            return true;
        }

        if ($activeJabatan->akses_surat_masuk === 'HANYA_DISPOSISI') {
            return false;
        }

        // Otherwise follow unit policy
        $unit = $unitId ? UnitKerja::find($unitId) : $activeJabatan->unitKerja;
        if (!$unit) {
            return true;
        }

        $kebijakan = $unit->getKebijakanSuratMasuk();
        if ($kebijakan === 'TERBUKA') {
            return true;
        }

        if ($kebijakan === 'LEVEL_JABATAN') {
            $threshold = $unit->getMinLevelJabatan();
            $myLevel = (int) ($activeJabatan->jabatan?->level_jabatan ?? 99);
            return $myLevel <= $threshold;
        }

        // TERBATAS_DISPOSISI
        return false;
    }

    /**
     * Determine whether the user is allowed to make disposisi in their unit.
     */
    public function canDisposisiUnit(?int $unitId = null): bool
    {
        if ($this->isKepalaUnit($unitId)) {
            return true;
        }

        $activeJabatan = $this->getActiveJabatan();
        return $activeJabatan?->can_disposisi === true;
    }
}
