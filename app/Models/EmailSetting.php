<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class EmailSetting extends Model
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
        $setting = Cache::remember("email_setting_{$key}", 3600, function () use ($key) {
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
        Cache::forget("email_setting_{$key}");

        return $result;
    }

    /**
     * Apply email settings to Laravel config
     */
    public static function applyToConfig(): void
    {
        $settings = static::where('is_active', true)->get()->keyBy('key');

        if ($settings->isNotEmpty()) {
            Config::set([
                'mail.default' => $settings->get('mail_mailer')?->typed_value ?? 'smtp',
                'mail.mailers.smtp.host' => $settings->get('mail_host')?->typed_value ?? 'localhost',
                'mail.mailers.smtp.port' => $settings->get('mail_port')?->typed_value ?? 587,
                'mail.mailers.smtp.username' => $settings->get('mail_username')?->typed_value,
                'mail.mailers.smtp.password' => $settings->get('mail_password')?->typed_value,
                'mail.mailers.smtp.encryption' => $settings->get('mail_encryption')?->typed_value ?? 'tls',
                'mail.from.address' => $settings->get('mail_from_address')?->typed_value ?? 'noreply@example.com',
                'mail.from.name' => $settings->get('mail_from_name')?->typed_value ?? 'RADTik',
            ]);
        }
    }

    /**
     * Get all SMTP-related settings
     */
    public static function getSmtpSettings(): array
    {
        return static::where('is_active', true)
            ->whereIn('key', [
                'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
                'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'
            ])
            ->get()
            ->keyBy('key')
            ->toArray();
    }
}
