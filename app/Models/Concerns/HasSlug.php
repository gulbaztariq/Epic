<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Keep the slug in sync with the title, and guarantee uniqueness.
     */
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->{static::slugSourceColumn()};

            if (blank($model->slug) && filled($source)) {
                $model->slug = static::uniqueSlug(Str::slug($source), $model->getKey());
            } elseif ($model->isDirty('slug') && filled($model->slug)) {
                $model->slug = static::uniqueSlug(Str::slug($model->slug), $model->getKey());
            }
        });
    }

    protected static function slugSourceColumn(): string
    {
        return 'title';
    }

    protected static function uniqueSlug(string $slug, $ignoreId = null): string
    {
        $slug = $slug !== '' ? $slug : 'item';
        $base = $slug;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
