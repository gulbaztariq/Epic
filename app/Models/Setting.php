<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'hint', 'sort'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public static function flush(): void
    {
        Cache::forget('epic.settings');
    }

    /**
     * All settings as a key => value map (cached for the request/app lifetime).
     */
    public static function all_values(): array
    {
        return Cache::rememberForever('epic.settings', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::all_values()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
