<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiToken extends Model
{
    use SoftDeletes;

    protected $table = 'ai_tokens';

    protected $fillable = [
        'provider',
        'label',
        'api_key',
        'model',
        'base_url',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get masked API key for safe UI display.
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (empty($this->api_key)) {
            return '-';
        }

        $prefixLen = min(8, strlen($this->api_key));
        return substr($this->api_key, 0, $prefixLen) . '••••••••';
    }

    /**
     * Model booted hooks.
     * Enforce single active token constraint on saving.
     */
    protected static function booted(): void
    {
        static::saving(function (AiToken $token) {
            if ($token->is_active) {
                static::where('id', '!=', $token->id ?? 0)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Activate this token and set all other tokens to inactive.
     */
    public function activate(): void
    {
        static::where('id', '!=', $this->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        static::where('id', $this->id)->update(['is_active' => true]);
        $this->is_active = true;
        $this->syncOriginalAttribute('is_active');
    }

    /**
     * Deactivate this token.
     */
    public function deactivate(): void
    {
        static::where('id', $this->id)->update(['is_active' => false]);
        $this->is_active = false;
        $this->syncOriginalAttribute('is_active');
    }
}
