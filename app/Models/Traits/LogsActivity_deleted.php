<?php

namespace App\Models\Traits;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity(): void
    {
        // Log when a model is created
        static::created(function (Model $model) {
            if (static::shouldLogActivity($model, 'created')) {
                ActivityLogger::logCreated($model);
            }
        });

        // Log when a model is updated
        static::updated(function (Model $model) {
            if (static::shouldLogActivity($model, 'updated')) {
                $changes = $model->getChanges();
                $original = array_intersect_key($model->getOriginal(), $changes);

                // Only log if there are actual changes
                if (! empty($changes) && ! empty($original)) {
                    ActivityLogger::logUpdated($model, $original, $changes);
                }
            }
        });

        // Log when a model is deleted
        static::deleted(function (Model $model) {
            if (static::shouldLogActivity($model, 'deleted')) {
                ActivityLogger::logDeleted($model);
            }
        });
    }

    /**
     * Determine if activity should be logged
     *
     * @param Model $model
     * @param string $action
     * @return bool
     */
    protected static function shouldLogActivity(Model $model, string $action): bool
    {
        // Don't log if no user is authenticated (e.g., console commands, jobs)
        // unless explicitly allowed
        if (! auth()->check() && ! property_exists($model, 'logWithoutAuth')) {
            return false;
        }

        // Check if model has specific actions to exclude
        if (property_exists($model, 'excludedActionsFromLog')) {
            if (in_array($action, $model->excludedActionsFromLog)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get activity logs for this model
     */
    public function activityLogs()
    {
        return $this->morphMany(\App\Models\ActivityLog::class, 'model');
    }
}
