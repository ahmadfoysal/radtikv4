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
        'user_id',
        'key',
        'value',
        'type',
        'description',
        'is_active',
    ];

    /**
     * Get the user that owns this setting
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for global settings (user_id is null)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope for user-specific settings
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

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
     * Get setting value by key with user context and caching
     * Falls back to global setting if user-specific setting not found
     */
    public static function getValue(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        // If no user ID provided, try to get from auth
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }

        $cacheKey = $userId ? "general_setting_{$userId}_{$key}" : "general_setting_global_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $userId) {
            // First, try to get user-specific setting
            if ($userId) {
                $userSetting = self::where('key', $key)
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->first();

                if ($userSetting) {
                    return $userSetting->typed_value;
                }
            }

            // If no user-specific setting found, get global setting
            $globalSetting = self::where('key', $key)
                ->whereNull('user_id')
                ->where('is_active', true)
                ->first();

            return $globalSetting ? $globalSetting->typed_value : $default;
        });
    }

    /**
     * Set setting value by key with user context
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?int $userId = null): bool
    {
        try {
            // If no user ID provided, try to get from auth (for user-specific settings)
            // For global settings, explicitly pass null
            if ($userId === null && auth()->check()) {
                $user = auth()->user();
                // Only set user_id for admin users, superadmin settings are global
                $userId = $user->isAdmin() ? $user->id : null;
            }

            $processedValue = match ($type) {
                'boolean' => $value ? 'true' : 'false',
                'integer' => (string) $value,
                'array' => json_encode($value),
                'string' => (string) $value,
                default => (string) $value,
            };

            self::updateOrCreate(
                ['key' => $key, 'user_id' => $userId],
                [
                    'value' => $processedValue,
                    'type' => $type,
                    'is_active' => true,
                ]
            );

            // Clear caches
            if ($userId) {
                Cache::forget("general_setting_{$userId}_{$key}");
            } else {
                Cache::forget("general_setting_global_{$key}");
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to set general setting: {$e->getMessage()}");
            return false;
        }
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
    public static function getAllSettings(?int $userId = null): array
    {
        $query = static::where('is_active', true);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }

        return $query->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();
    }

    /**
     * Get user-specific settings with fallback to global settings
     */
    public static function getUserSettingsWithDefaults(?int $userId = null): array
    {
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }

        $userSettings = static::where('is_active', true)
            ->where('user_id', $userId)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();

        $globalSettings = static::where('is_active', true)
            ->whereNull('user_id')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();

        return array_merge($globalSettings, $userSettings);
    }

    /**
     * Get company information settings for a user
     */
    public static function getCompanyInfo(?int $userId = null): array
    {
        $companyKeys = [
            'company_name',
            'company_logo',
            'company_address',
            'company_phone',
            'company_email',
            'company_website'
        ];

        $result = [];
        foreach ($companyKeys as $key) {
            $result[$key] = self::getValue($key, '', $userId);
        }

        return $result;
    }

    /**
     * Get platform settings (superadmin only)
     */
    public static function getPlatformSettings(): array
    {
        return static::where('is_active', true)
            ->whereNull('user_id')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();
    }
}
