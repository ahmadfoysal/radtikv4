<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity
     *
     * @param string $action The action performed (created, updated, deleted, etc.)
     * @param Model|null $model The model affected
     * @param string|null $description Human-readable description
     * @param array|null $oldValues Old values before change
     * @param array|null $newValues New values after change
     * @param int|null $userId User who performed the action
     * @return ActivityLog
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): ActivityLog {
        $userId = $userId ?? Auth::id();

        $data = [
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description ?? static::generateDescription($action, $model),
            'old_values' => $oldValues ? static::sanitizeValues($oldValues) : null,
            'new_values' => $newValues ? static::sanitizeValues($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        return ActivityLog::create($data);
    }

    /**
     * Generate a description based on action and model
     */
    protected static function generateDescription(string $action, ?Model $model): string
    {
        if (! $model) {
            return ucfirst(\App\Support\ActivityLogHelper::humanize($action));
        }

        $modelName = \App\Support\ActivityLogHelper::getReadableModelName($model);
        $identifier = \App\Support\ActivityLogHelper::getModelIdentifier($model);
        $actionText = \App\Support\ActivityLogHelper::humanize($action);

        // Generate contextual description
        return match($action) {
            'created' => "Created new {$modelName}: {$identifier}",
            'updated' => "Updated {$modelName}: {$identifier}",
            'deleted' => "Deleted {$modelName}: {$identifier}",
            default => ucfirst($actionText) . " {$modelName}: {$identifier}",
        };
    }

    /**
     * Sanitize values to remove sensitive data
     */
    protected static function sanitizeValues(array $values): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($values[$key])) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * Log model creation
     */
    public static function logCreated(Model $model, ?array $attributes = null): ActivityLog
    {
        $attributes = $attributes ?? $model->getAttributes();

        return static::log(
            'created',
            $model,
            null,
            null,
            $attributes
        );
    }

    /**
     * Log model update
     */
    public static function logUpdated(Model $model, array $oldValues, array $newValues): ActivityLog
    {
        return static::log(
            'updated',
            $model,
            null,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log model deletion
     */
    public static function logDeleted(Model $model, ?array $attributes = null): ActivityLog
    {
        $attributes = $attributes ?? $model->getAttributes();

        return static::log(
            'deleted',
            $model,
            null,
            $attributes,
            null
        );
    }

    /**
     * Log custom action
     */
    public static function logCustom(
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $data = null
    ): ActivityLog {
        return static::log(
            $action,
            $model,
            $description,
            null,
            $data
        );
    }
}
