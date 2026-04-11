<?php

namespace App\Http\Controllers\ShortLinks;

use App\Http\Controllers\Controller;
use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Support\ShortLinkDestination;
use App\Support\UserAgentInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectShortLinkController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $slug = mb_strtolower($slug);

        $link = ShortLink::query()->where('slug', $slug)->first();

        if ($link === null || ! $link->is_active) {
            abort(404);
        }

        if ($link->isExpired()) {
            abort(410);
        }

        $target = ShortLinkDestination::resolved($link, $request);

        $insights = UserAgentInsights::fromRequest($request);

        LinkClick::query()->create([
            'short_link_id' => $link->id,
            'ip_hash' => hash_hmac('sha256', $request->ip() ?? '', (string) config('app.key')),
            'referrer' => $request->headers->get('referer'),
            ...$insights,
            'created_at' => now(),
        ]);

        return redirect()->away($target);
    }
}
