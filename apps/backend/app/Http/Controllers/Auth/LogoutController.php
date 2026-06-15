<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CloseOfficerSessions;
use App\Actions\Auth\RevokeCurrentOfficerToken;
use App\Http\Controllers\Controller;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Authentication
 */
class LogoutController extends Controller
{
    public function __construct(
        private readonly RevokeCurrentOfficerToken $revokeCurrentOfficerToken,
        private readonly CloseOfficerSessions $closeOfficerSessions,
    ) {
    }

    /**
     * Revoke the current bearer token for the authenticated actor.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $actor = $request->user();

        if ($actor === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $actor->currentAccessToken();

        if ($actor instanceof Officer && $token !== null) {
            $this->closeOfficerSessions->closeForToken($actor, $token);
        }

        if ($actor instanceof Officer) {
            $this->revokeCurrentOfficerToken->revoke($actor);
        } else {
            $token?->delete();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
