<?php

namespace App\Http\Middleware;

use App\Models\Officer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficerHubActive
{
    /**
     * Routes officers may call without a hub-active session (Tier B:
     * profile/auth, reference reads, officer show, issue browse including
     * duplicates, participants index, status history, community post browse,
     * and resolution/community-post attachment downloads). All other protected
     * API routes return 403 (Tier C).
     *
     * @var list<string>
     */
    private const WHITELISTED_ROUTE_NAMES = [
        'auth.me',
        'auth.me.update',
        'auth.logout',
        'auth.start-shift',
        'auth.me.districts.update',
        'hubs.index',
        'hubs.show',
        'districts.index',
        'districts.show',
        'categories.index',
        'categories.show',
        'officer-sessions.index',
        'officers.show',
        'issues.index',
        'issues.show',
        'issues.duplicates.index',
        'issues.participants.index',
        'issues.status-history.index',
        'issues.comments.index',
        'issues.officer-resolution.show',
        'issues.officer-resolution.attachments.download',
        'issues.attachments.download',
        'issues.officer-updates.index',
        'issues.officer-updates.attachments.download',
        'issues.feedback.index',
        'officers.me.feedback.index',
        'community-posts.index',
        'community-posts.show',
        'community-posts.attachments.download',
        'issues.chats.index',
        'issues.chats.messages.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (! $actor instanceof Officer) {
            return $next($request);
        }

        if ($this->isWhitelisted($request)) {
            return $next($request);
        }

        if ($actor->is_active && $actor->hub_active_until?->isFuture()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Hub-active session required.',
            'code' => 'hub_active_required',
        ], Response::HTTP_FORBIDDEN);
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
