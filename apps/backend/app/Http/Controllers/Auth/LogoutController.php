<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CloseOfficerSessions;
use App\Actions\Auth\RevokeOfficerHubActive;
use App\Http\Controllers\Controller;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __construct(
        private readonly RevokeOfficerHubActive $revokeOfficerHubActive,
        private readonly CloseOfficerSessions $closeOfficerSessions,
    ) {
    }

    /**
     * Invalidate all Sanctum personal access tokens for the authenticated actor.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $actor = $request->user();

        if ($actor === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($actor instanceof Officer) {
            $this->revokeOfficerHubActive->revoke($actor);
            $this->closeOfficerSessions->closeFor($actor);
        } else {
            $actor->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
