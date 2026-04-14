<?php

namespace App\Http\Controllers\ShortLinks;

use App\Http\Controllers\Controller;
use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Support\BotEventRecorder;
use App\Support\BotTrafficClassifier;
use App\Support\BotTrafficRateLimiterKeys;
use App\Support\ClickGeoLocator;
use App\Support\ShortLinkDestination;
use App\Support\UserAgentInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        $geo = ClickGeoLocator::fromClientIp($request->ip());

        if (BotTrafficClassifier::isLikelyAutomated($request)) {
            $limitKey = BotTrafficRateLimiterKeys::redirectLikelyBot($request, $slug);
            $maxAttempts = max(1, (int) config('bot-detection.redirect.max_bot_attempts_per_window', 40));
            $decaySeconds = max(1, (int) config('bot-detection.redirect.decay_seconds', 60));

            if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
                BotEventRecorder::record(
                    $request,
                    $link->id,
                    'redirect',
                    'rate_limited',
                    action: 'follow_short_url',
                );

                abort(429);
            }

            RateLimiter::hit($limitKey, $decaySeconds);

            BotEventRecorder::record(
                $request,
                $link->id,
                'redirect',
                BotTrafficClassifier::automatedReasonCode($request),
                action: 'follow_short_url',
            );
        } else {
            LinkClick::query()->create([
                'short_link_id' => $link->id,
                'ip_hash' => hash_hmac('sha256', $request->ip() ?? '', (string) config('app.key')),
                'referrer' => $request->headers->get('referer'),
                ...$insights,
                ...$geo,
                'created_at' => now(),
            ]);
        }

        return redirect()->away($target);
    }
}
