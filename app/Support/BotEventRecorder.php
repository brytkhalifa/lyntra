<?php

namespace App\Support;

use App\Models\BotEvent;
use Illuminate\Http\Request;

final class BotEventRecorder
{
    private const MAX_USER_AGENT_LENGTH = 512;

    /**
     * @param  'redirect'|'resolver'  $endpoint
     */
    public static function record(
        Request $request,
        ?int $shortLinkId,
        string $endpoint,
        string $reason,
        ?string $action = null,
    ): void {
        $ua = (string) $request->userAgent();
        if (mb_strlen($ua) > self::MAX_USER_AGENT_LENGTH) {
            $ua = mb_substr($ua, 0, self::MAX_USER_AGENT_LENGTH);
        }

        $insights = UserAgentInsights::fromRequest($request);
        $geo = ClickGeoLocator::fromClientIp($request->ip());

        BotEvent::query()->create([
            'short_link_id' => $shortLinkId,
            'endpoint' => $endpoint,
            'action' => $action,
            'reason' => $reason,
            'ip_hash' => hash_hmac('sha256', $request->ip() ?? '', (string) config('app.key')),
            'user_agent' => $ua === '' ? null : $ua,
            'referrer' => $request->headers->get('referer'),
            ...$insights,
            ...$geo,
            'created_at' => now(),
        ]);
    }
}
