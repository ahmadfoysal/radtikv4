<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo('model');
    }

    /**
     * Get a human-readable summary of this activity
     */
    public function getReadableSummaryAttribute(): string
    {
        $userName = $this->user ? $this->user->name : 'System';
        $modelName = $this->getReadableModelName();
        $action = $this->getReadableAction();
        $identifier = $this->getModelIdentifier();
        
        // Include identifier for more context
        if ($identifier) {
            return "{$userName} {$action} {$modelName}: {$identifier}";
        }
        
        return "{$userName} {$action} {$modelName}";
    }

    /**
     * Get human-readable model name
     */
    public function getReadableModelName(): string
    {
        if (!$this->model_type) {
            return 'an item';
        }

        return \App\Support\ActivityLogHelper::getReadableModelName($this->model_type);
    }

    /**
     * Get model identifier for display
     */
    protected function getModelIdentifier(): ?string
    {
        // Try to get from description first (for custom logs)
        if ($this->description && preg_match('/: (.+)$/', $this->description, $matches)) {
            return $matches[1];
        }

        // Try to get from stored values
        $values = $this->new_values ?? $this->old_values;
        if (!$values) {
            return null;
        }

        $identifierFields = ['name', 'title', 'username', 'email', 'subject'];
        foreach ($identifierFields as $field) {
            if (isset($values[$field]) && !empty($values[$field])) {
                return $values[$field];
            }
        }

        return null;
    }

    /**
     * Get human-readable action
     */
    public function getReadableAction(): string
    {
        return match($this->action) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'bulk_generated' => 'generated multiple',
            'bulk_deleted' => 'deleted multiple',
            'routers_assigned' => 'assigned routers to',
            'routers_unassigned' => 'removed routers from',
            default => \App\Support\ActivityLogHelper::humanize($this->action),
        };
    }

    /**
     * Get formatted changes for display
     */
    public function getFormattedChangesAttribute(): ?string
    {
        if ($this->action === 'created') {
            return $this->formatCreatedChanges();
        }

        if ($this->action === 'updated' && $this->old_values && $this->new_values) {
            return $this->formatUpdateChanges();
        }

        if ($this->action === 'deleted' && $this->old_values) {
            return $this->formatDeletedChanges();
        }

        return null;
    }

    /**
     * Format changes for created items
     */
    protected function formatCreatedChanges(): ?string
    {
        if (!$this->new_values) {
            return null;
        }

        $important = $this->getImportantFields($this->new_values);
        
        if (empty($important)) {
            return null;
        }

        $parts = [];
        foreach ($important as $key => $value) {
            $label = $this->humanizeFieldName($key);
            $parts[] = "{$label}: {$value}";
        }

        return implode(', ', $parts);
    }

    /**
     * Format changes for updated items
     */
    protected function formatUpdateChanges(): string
    {
        $changes = [];
        
        foreach ($this->new_values as $key => $newValue) {
            if (isset($this->old_values[$key])) {
                $oldValue = $this->old_values[$key];
                
                // Skip if values are the same
                if ($oldValue === $newValue) {
                    continue;
                }

                $label = $this->humanizeFieldName($key);
                $changes[] = "{$label} changed from '{$oldValue}' to '{$newValue}'";
            }
        }

        return empty($changes) ? 'No significant changes' : implode(', ', $changes);
    }

    /**
     * Format changes for deleted items
     */
    protected function formatDeletedChanges(): ?string
    {
        $important = $this->getImportantFields($this->old_values);
        
        if (empty($important)) {
            return null;
        }

        $parts = [];
        foreach ($important as $key => $value) {
            $label = $this->humanizeFieldName($key);
            $parts[] = "{$label}: {$value}";
        }

        return 'Deleted: ' . implode(', ', $parts);
    }

    /**
     * Get important fields for display
     */
    protected function getImportantFields(array $values): array
    {
        // Fields to always skip
        $skipFields = [
            'id', 'created_at', 'updated_at', 'deleted_at', 
            'password', 'remember_token', 'two_factor_secret',
            'two_factor_recovery_codes', 'user_agent', 'ip_address'
        ];

        // Priority fields to show
        $priorityFields = ['name', 'username', 'email', 'title', 'subject', 'status'];

        $important = [];
        
        // First, add priority fields if they exist
        foreach ($priorityFields as $field) {
            if (isset($values[$field]) && !in_array($field, $skipFields)) {
                $important[$field] = $values[$field];
            }
        }

        // If we don't have enough important fields, add others (limit to 3 total)
        if (count($important) < 3) {
            foreach ($values as $key => $value) {
                if (count($important) >= 3) {
                    break;
                }

                if (!in_array($key, $skipFields) && !isset($important[$key]) && is_scalar($value)) {
                    $important[$key] = $value;
                }
            }
        }

        return $important;
    }

    /**
     * Humanize field name
     */
    protected function humanizeFieldName(string $field): string
    {
        return \App\Support\ActivityLogHelper::humanizeFieldName($field);
    }

    /**
     * Get time ago in human readable format
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
