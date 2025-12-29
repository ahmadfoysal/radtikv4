<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class GeneralSetting extends Model
{

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
     * Falls back to parent admin (for resellers) or global setting
     * 
     * Priority: User's setting > Parent admin's setting (for resellers) > Global setting > Default
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

                // If user is a reseller, try to get parent admin's setting
                $user = User::find($userId);
                if ($user && $user->isReseller() && $user->admin_id) {
                    $parentSetting = self::where('key', $key)
                        ->where('user_id', $user->admin_id)
                        ->where('is_active', true)
                        ->first();

                    if ($parentSetting) {
                        return $parentSetting->typed_value;
                    }
                }
            }

            // If no user-specific or parent setting found, get global setting
            $globalSetting = self::where('key', $key)
                ->whereNull('user_id')
                ->where('is_active', true)
                ->first();

            return $globalSetting ? $globalSetting->typed_value : $default;
        });
    }

    /**
     * Set setting value by key with user context
     * 
     * - SuperAdmin: Settings are global (user_id = null)
     * - Admin: Settings are user-specific (user_id = admin's id)
     * - Reseller: Settings are user-specific (user_id = reseller's id)
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?int $userId = null): bool
    {
        try {
            // If userId is explicitly provided, use it
            // Otherwise determine based on authenticated user's role
            if ($userId === null && auth()->check()) {
                $user = auth()->user();
                // SuperAdmin settings are global (user_id = null)
                // Admin and Reseller settings are user-specific
                $userId = $user->isSuperAdmin() ? null : $user->id;
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
     * Apply global platform settings to Laravel config
     * Only applies settings set by superadmin (user_id = null)
     * User-specific settings should be applied per-user, not globally
     */
    public static function applyToConfig(): void
    {
        // Only get global settings (where user_id is null)
        $settings = static::where('is_active', true)
            ->whereNull('user_id')
            ->get()
            ->keyBy('key');

        if ($settings->isNotEmpty()) {
            Config::set([
                'app.name' => $settings->get('platform_name')?->typed_value ?? config('app.name'),
                'app.timezone' => $settings->get('default_timezone')?->typed_value ?? config('app.timezone'),
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

    /**
     * Apply user-specific settings to config for the current request
     * This should be called in middleware for each authenticated user
     */
    public static function applyUserConfig(?int $userId = null): void
    {
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }

        if (!$userId) {
            return;
        }

        // Get user's timezone (with fallback to parent admin or global)
        $timezone = self::getValue('timezone', config('app.timezone'), $userId);
        if ($timezone) {
            Config::set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Get formatted date according to user's preference
     */
    public static function formatDate($date, ?int $userId = null): string
    {
        $format = self::getValue('date_format', 'Y-m-d', $userId);
        return $date instanceof \DateTime ? $date->format($format) : date($format, strtotime($date));
    }

    /**
     * Get formatted time according to user's preference
     */
    public static function formatTime($time, ?int $userId = null): string
    {
        $format = self::getValue('time_format', 'H:i:s', $userId);
        return $time instanceof \DateTime ? $time->format($format) : date($format, strtotime($time));
    }

    /**
     * Get formatted datetime according to user's preference
     */
    public static function formatDateTime($datetime, ?int $userId = null): string
    {
        $dateFormat = self::getValue('date_format', 'Y-m-d', $userId);
        $timeFormat = self::getValue('time_format', 'H:i:s', $userId);
        $format = $dateFormat . ' ' . $timeFormat;
        return $datetime instanceof \DateTime ? $datetime->format($format) : date($format, strtotime($datetime));
    }

    /**
     * Get currency symbol for user
     */
    public static function getCurrencySymbol(?int $userId = null): string
    {
        return self::getValue('currency_symbol', '$', $userId);
    }

    /**
     * Get currency code for user
     */
    public static function getCurrency(?int $userId = null): string
    {
        return self::getValue('currency', 'USD', $userId);
    }
}
