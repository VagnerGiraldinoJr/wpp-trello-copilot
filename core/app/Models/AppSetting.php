<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Read a setting value, JSON-decoding it when possible.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = Cache::rememberForever("app_setting:{$key}", function () use ($key) {
            return static::query()->where('key', $key)->value('value');
        });

        if ($raw === null) {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    /**
     * Persist a setting value, JSON-encoding arrays/objects/bools automatically.
     */
    public static function set(string $key, mixed $value): void
    {
        $stored = is_string($value) ? $value : json_encode($value);

        static::query()->updateOrCreate(['key' => $key], ['value' => $stored]);

        Cache::forget("app_setting:{$key}");
    }

    public static function many(array $keys, mixed $default = null): array
    {
        return collect($keys)->mapWithKeys(fn (string $key) => [$key => static::get($key, $default)])->all();
    }
}
