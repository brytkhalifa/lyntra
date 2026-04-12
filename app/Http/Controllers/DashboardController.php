<?php

namespace App\Http\Controllers;

use App\Support\AccountShortLinkAnalytics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        return Inertia::render('Dashboard', [
            'summary' => AccountShortLinkAnalytics::summary($user),
            'clicks_by_day' => AccountShortLinkAnalytics::clicksByDay($user, 30),
            'top_links' => AccountShortLinkAnalytics::topLinksByClicks($user, 5),
            'recent_links' => AccountShortLinkAnalytics::recentLinks($user, 5),
        ]);
    }
}
