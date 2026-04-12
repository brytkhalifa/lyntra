<?php

namespace App\Http\Controllers\ShortLinks;

use App\Exceptions\UnsafeUrlResolutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveShortUrlRequest;
use App\Models\ShortLink;
use App\Support\ShortUrlResolver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ResolveShortUrlController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(ResolveShortUrlRequest $request): JsonResponse
    {
        $this->authorize('resolve', ShortLink::class);

        try {
            $payload = ShortUrlResolver::resolve(
                $request->validated('url'),
                $request,
            );
        } catch (UnsafeUrlResolutionException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json($payload);
    }
}
