<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Key/value settings owned by the platform admin and shared by every
 * client - deliberately has no company_id.
 */
class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getSecret(string $key): ?string
    {
        $value = static::get($key);

        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // APP_KEY changed since it was saved - treat as not set, so the
            // platform admin is asked to enter it again.
            return null;
        }
    }

    public static function setSecret(string $key, ?string $value): void
    {
        static::set($key, $value === null ? null : Crypt::encryptString($value));
    }
}
