<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgebaseArticle extends Model
{
    use LogsActivity;
    protected $fillable = [
        'title',
        'slug',
        'category',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model and automatically generate unique slug.
     */
    protected static function booted(): void
    {
        static::creating(function (KnowledgebaseArticle $article) {
            $article->slug = $article->slug ?: static::uniqueSlugFrom($article->title);
        });

        static::updating(function (KnowledgebaseArticle $article) {
            if ($article->isDirty('title') && ! $article->isDirty('slug')) {
                $article->slug = static::uniqueSlugFrom($article->title, $article->id);
            }
        });
    }

    /**
     * Generate a unique slug from the given title.
     */
    protected static function uniqueSlugFrom(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
