<?php

namespace App\Http\Controllers\ShortLinks;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortLinkRequest;
use App\Http\Requests\UpdateShortLinkRequest;
use App\Models\ShortLink;
use App\Support\ShortLinkAnalytics;
use App\Support\UniqueShortSlug;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ShortLinkController extends Controller
{
    use AuthorizesRequests;

    public function expand(): Response
    {
        $this->authorize('resolve', ShortLink::class);

        return Inertia::render('Links/Resolve');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ShortLink::class);

        $shortLinks = $request->user()
            ->shortLinks()
            ->withCount('linkClicks')
            ->latest()
            ->paginate(15)
            ->through(fn (ShortLink $link): array => [
                'id' => $link->id,
                'slug' => $link->slug,
                'short_url' => url('/'.$link->slug),
                'destination_url' => $link->destination_url,
                'is_active' => $link->is_active,
                'expires_at' => $link->expires_at?->toIso8601String(),
                'created_at' => $link->created_at->toIso8601String(),
                'clicks_count' => $link->link_clicks_count,
            ]);

        return Inertia::render('Links/Index', [
            'shortLinks' => $shortLinks,
        ]);
    }

    public function show(Request $request, ShortLink $link): Response
    {
        $this->authorize('view', $link);

        return Inertia::render('Links/Show', [
            'shortLink' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'short_url' => url('/'.$link->slug),
                'destination_url' => $link->destination_url,
                'is_active' => $link->is_active,
                'expires_at' => $link->expires_at?->toIso8601String(),
                'created_at' => $link->created_at->toIso8601String(),
            ],
            'analytics' => [
                'total_clicks' => ShortLinkAnalytics::totalClicks($link->id),
                'filtered_bot_events_30d' => ShortLinkAnalytics::filteredBotEventsLast30Days($link->id),
                'clicks_by_day' => ShortLinkAnalytics::clicksByDay($link->id, 30),
                'by_device' => ShortLinkAnalytics::breakdown($link->id, 'device_type'),
                'by_browser' => ShortLinkAnalytics::breakdown($link->id, 'browser'),
                'by_os' => ShortLinkAnalytics::breakdown($link->id, 'os'),
                'by_country' => ShortLinkAnalytics::breakdown($link->id, 'country_code'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ShortLink::class);

        return Inertia::render('Links/Create', [
            'prefill_destination_url' => $this->prefillDestinationUrlFromQuery($request),
        ]);
    }

    public function store(StoreShortLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $validated['slug'] ??= UniqueShortSlug::generate();

        ShortLink::query()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link created.')]);

        return to_route('links.index');
    }

    /**
     * Optional `destination_url` query for the create form (e.g. from the landing page).
     */
    private function prefillDestinationUrlFromQuery(Request $request): ?string
    {
        if (! $request->has('destination_url')) {
            return null;
        }

        $value = $request->query('destination_url');
        if (! is_string($value)) {
            return null;
        }

        $value = mb_substr(trim($value), 0, 2048);
        if ($value === '') {
            return null;
        }

        $validator = Validator::make(
            ['destination_url' => $value],
            ['destination_url' => ['required', 'string', 'max:2048', 'regex:/^https?:\\/\\/.+/i']],
        );

        return $validator->passes() ? $value : null;
    }

    public function edit(Request $request, ShortLink $link): Response
    {
        $this->authorize('update', $link);

        return Inertia::render('Links/Edit', [
            'shortLink' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'short_url' => url('/'.$link->slug),
                'destination_url' => $link->destination_url,
                'utm_source' => $link->utm_source,
                'utm_medium' => $link->utm_medium,
                'utm_campaign' => $link->utm_campaign,
                'utm_term' => $link->utm_term,
                'utm_content' => $link->utm_content,
                'expires_at' => $link->expires_at?->format('Y-m-d\TH:i'),
                'is_active' => $link->is_active,
            ],
        ]);
    }

    public function update(UpdateShortLinkRequest $request, ShortLink $link): RedirectResponse
    {
        $link->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link updated.')]);

        return to_route('links.index');
    }

    public function destroy(Request $request, ShortLink $link): RedirectResponse
    {
        $this->authorize('delete', $link);

        $link->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link deleted.')]);

        return to_route('links.index');
    }
}
