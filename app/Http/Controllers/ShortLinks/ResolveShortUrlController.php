<?php

namespace App\Http\Controllers\ShortLinks;

use App\Exceptions\UnsafeUrlResolutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveShortUrlRequest;
use App\Models\ShortLink;
use App\Support\BotEventRecorder;
use App\Support\BotTrafficClassifier;
use App\Support\BotTrafficRateLimiterKeys;
use App\Support\ShortUrlResolver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class ResolveShortUrlController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(ResolveShortUrlRequest $request): JsonResponse
    {
        $this->authorize('resolve', ShortLink::class);

        $user = $request->user();
        abort_unless($user !== null, 401);

        $inputUrl = $request->validated('url');
        $automated = BotTrafficClassifier::isLikelyAutomated($request);

        if ($automated) {
            $limitKey = BotTrafficRateLimiterKeys::resolverLikelyBot($request, $user->id);
            $maxAttempts = max(1, (int) config('bot-detection.resolver.max_bot_attempts_per_window', 20));
            $decaySeconds = max(1, (int) config('bot-detection.resolver.decay_seconds', 60));

            if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
                BotEventRecorder::record(
                    $request,
                    ShortUrlResolver::firstInternalShortLinkIdFromInputUrl($inputUrl),
                    'resolver',
                    'rate_limited',
                    action: 'resolve_url',
                );

                abort(429);
            }

            RateLimiter::hit($limitKey, $decaySeconds);
        }

        try {
            $payload = ShortUrlResolver::resolve(
                $inputUrl,
                $request,
            );
        } catch (UnsafeUrlResolutionException $e) {
            abort(422, $e->getMessage());
        }

        if ($automated) {
            BotEventRecorder::record(
                $request,
                ShortUrlResolver::firstInternalShortLinkIdFromInputUrl($inputUrl),
                'resolver',
                BotTrafficClassifier::automatedReasonCode($request),
                action: 'resolve_url',
            );
        }

        return response()->json($payload);
    }
}
