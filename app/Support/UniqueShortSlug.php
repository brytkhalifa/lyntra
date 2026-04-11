<?php

namespace App\Support;

use App\Models\ShortLink;
use Illuminate\Support\Str;

final class UniqueShortSlug
{
    public static function generate(): string
    {
        do {
            $slug = Str::lower(Str::random(8));
        } while (ShortLink::query()->where('slug', $slug)->exists() || ReservedSlugs::contains($slug));

        return $slug;
    }
}
