<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global, block-agnostic settings store: a `key` and a free-form JSON
 * `value`, so any block can keep its own configurable numbers/tables in the
 * database instead of hardcoding them, without XMS needing to know their
 * shape ahead of time.
 */
class Configuration extends Model
{
    protected $table = 'xms_configurations';

    protected $fillable = [
        'key',
        'label',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->firstWhere('key', $key)?->value ?? $default;
    }

    public static function set(string $key, mixed $value, ?string $label = null): self
    {
        $config = static::firstOrNew(['key' => $key]);
        $config->value = $value;

        if ($label !== null) {
            $config->label = $label;
        }

        $config->save();

        return $config;
    }
}
