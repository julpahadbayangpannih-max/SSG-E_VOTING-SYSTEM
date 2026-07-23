<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'setting_key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['setting_key', 'setting_value'];

    /**
     * Convenience: get a single setting value.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::find($key)?->setting_value ?? $default;
    }

    /**
     * Convenience: upsert a single setting.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }
}
