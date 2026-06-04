<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActorIsActive
{
    /**
     * Routes an inactive authenticated actor may still call (profile recovery
     * and token cleanup). All other protected API routes return 403.
     *
     * @var list<string>
     */
    private const WHITELISTED_ROUTE_NAMES = [
        'auth.me',
        'auth.logout',
        'auth.me.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if ($actor === null) {
            return $next($request);
        }

        if (array_key_exists('is_active', $actor->getAttributes()) && ! (bool) $actor->getAttribute('is_active')) {
            if ($this->isWhitelisted($request)) {
                return $next($request);
            }

            abort(403, 'This account is inactive.');
        }

        return $next($request);
    }

    private function isWhitelisted(Request $request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        $name = $route->getName();

        return $name !== null && in_array($name, self::WHITELISTED_ROUTE_NAMES, true);
    }
}
