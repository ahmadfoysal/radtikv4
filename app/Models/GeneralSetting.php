<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class GeneralSetting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the typed value based on the type field
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'array' => json_decode($this->value, true) ?? [],
            'string' => $this->value,
            default => $this->value,
        };
    }

    /**
     * Set value with proper type casting
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'integer' => (string) $value,
            'array' => json_encode($value),
            'string' => (string) $value,
            default => (string) $value,
        };
    }

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = Cache::remember("general_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->where('is_active', true)->first();
        });

        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function setValue(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->setTypedValue($value);
        $result = $setting->save();

        // Clear cache
        Cache::forget("general_setting_{$key}");

        return $result;
    }

    /**
     * Apply general settings to Laravel config
     */
    public static function applyToConfig(): void
    {
        $settings = static::where('is_active', true)->get()->keyBy('key');

        if ($settings->isNotEmpty()) {
            Config::set([
                'app.name' => $settings->get('company_name')?->typed_value ?? config('app.name'),
                'app.timezone' => $settings->get('timezone')?->typed_value ?? config('app.timezone'),
            ]);
        }
    }

    /**
     * Get all general settings as key-value array
     */
    public static function getAllSettings(): array
    {
        return static::where('is_active', true)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();
    }

    /**
     * Get company information settings
     */
    public static function getCompanyInfo(): array
    {
        return static::where('is_active', true)
            ->whereIn('key', [
                'company_name',
                'company_logo',
                'company_address',
                'company_phone',
                'company_email',
                'company_website'
            ])
            ->get()
            ->keyBy('key')
            ->toArray();
    }
}
