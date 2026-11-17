<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $value
 */
#[Fillable(['key', 'value', 'description'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        $raw = $setting->value;

        return is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): self
    {
        $payload = ['value' => ['value' => $value]];

        if ($description !== null) {
            $payload['description'] = $description;
        }

        return static::query()->updateOrCreate(['key' => $key], $payload);
    }
}
