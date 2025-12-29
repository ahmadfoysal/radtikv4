<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Zone extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Zone $zone) {
            $zone->slug = $zone->slug ?: static::uniqueSlugFrom($zone->name);
            $zone->color = $zone->color ?: '#2563eb';
        });

        static::updating(function (Zone $zone) {
            if ($zone->isDirty('name') && ! $zone->isDirty('slug')) {
                $zone->slug = static::uniqueSlugFrom($zone->name, $zone->id);
            }
        });
    }

    protected static function uniqueSlugFrom(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn(Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    public function scopeOwnedBy(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }
}
