<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
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

        $actor->tokens()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
