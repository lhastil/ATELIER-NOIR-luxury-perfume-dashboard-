<?php

namespace App\View;

use Illuminate\Support\Str;
use Illuminate\View\Factory;

/**
 * Element ids for Blade components that are unique within one page render.
 *
 * An id is derived from the component's label ("stat-total-orders"); a label
 * that repeats on the page gets a numeric suffix ("stat-total-orders-2"). Ids
 * are recorded in the view factory's per-render "once" registry, which Laravel
 * clears after every top-level render, so a page always renders the same ids.
 */
class ComponentId
{
    /**
     * An explicit id is used as given and reserved, so ids generated after it on
     * the page skip it. Collisions with an id generated earlier on the page, or
     * with another explicit id, are not prevented: explicit ids are the caller's
     * responsibility.
     */
    public static function for(Factory $views, string $prefix, ?string $label, ?string $explicit = null): string
    {
        if ($explicit !== null && $explicit !== '') {
            self::reserve($views, $explicit);

            return $explicit;
        }

        // Labels with no letters or digits (e.g. punctuation only) still get a valid id.
        $base = $prefix.'-'.(Str::slug((string) $label) ?: 'item');
        $id = $base;

        for ($suffix = 2; $views->hasRenderedOnce(self::key($id)); $suffix++) {
            $id = $base.'-'.$suffix;
        }

        self::reserve($views, $id);

        return $id;
    }

    private static function reserve(Factory $views, string $id): void
    {
        $views->markAsRenderedOnce(self::key($id));
    }

    private static function key(string $id): string
    {
        return 'component-id:'.$id;
    }
}
