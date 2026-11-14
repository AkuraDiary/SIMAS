<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retrieve a setting value by key, returning default if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::query()->find($key);
            return $setting !== null ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set a setting value by key (updates or creates).
     */
    public static function set(string $key, mixed $value): self
    {
        $normalizedValue = is_bool($value)
            ? ($value ? '1' : '0')
            : (string) $value;

        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $normalizedValue]
        );
    }

    /**
     * Check if a boolean setting evaluates to true.
     */
    public static function isTrue(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
