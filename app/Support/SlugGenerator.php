<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SlugGenerator
{
    /**
     * Generates a slug from $title and appends "-1", "-2", etc. until it's
     * unique for the given model/field. Pass $ignoreRecord when editing so
     * the record doesn't collide with its own current slug, and
     * $queryModifier when uniqueness is scoped (e.g. Subcategory is unique
     * per category_id, not globally).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function unique(
        string $modelClass,
        string $title,
        ?Model $ignoreRecord = null,
        string $field = 'slug',
        string $separator = '-',
        ?Closure $queryModifier = null,
    ): string {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidArgumentException('Cannot generate a slug from an empty title.');
        }

        $slug         = Str::slug($title, $separator);
        $originalSlug = $slug;
        $attempt      = 1;

        while (
            $modelClass::withoutGlobalScopes()
                ->where($field, $slug)
                ->when($ignoreRecord, fn ($query) => $query->whereKeyNot($ignoreRecord->getKey()))
                ->when($queryModifier, fn ($query) => $queryModifier($query))
                ->exists()
        ) {
            $slug = "{$originalSlug}{$separator}{$attempt}";
            $attempt++;
        }

        return $slug;
    }
}