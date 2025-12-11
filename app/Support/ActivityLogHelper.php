<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogHelper
{
    /**
     * Convert a string to human-readable format (snake_case to words)
     */
    public static function humanize(string $text): string
    {
        return Str::of($text)
            ->snake()
            ->replace('_', ' ')
            ->lower()
            ->toString();
    }

    /**
     * Get readable model name from model class
     */
    public static function getReadableModelName(Model|string $model): string
    {
        $className = is_string($model) ? $model : get_class($model);
        $modelName = class_basename($className);
        
        return static::humanize($modelName);
    }

    /**
     * Get model identifier for display
     */
    public static function getModelIdentifier(Model $model): string
    {
        // Try custom getIdentifier method first
        if (method_exists($model, 'getIdentifier')) {
            return $model->getIdentifier();
        }

        // Try common identifier fields
        $identifierFields = ['name', 'title', 'username', 'email', 'subject'];
        
        foreach ($identifierFields as $field) {
            if (isset($model->{$field}) && !empty($model->{$field})) {
                return $model->{$field};
            }
        }

        // Fall back to ID
        return "#{$model->id}";
    }

    /**
     * Convert field name to human-readable label
     */
    public static function humanizeFieldName(string $field): string
    {
        return Str::of($field)
            ->snake()
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
