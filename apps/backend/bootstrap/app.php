<?php

use App\Http\Middleware\EnsureActorIsActive;
use App\Http\Middleware\EnsureOfficerHubActive;
use App\Support\OfficerIssueConflict;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'actor.active' => EnsureActorIsActive::class,
            'officer.hub-active' => EnsureOfficerHubActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $expectsApiJson = static function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        };

        $isIntegrityConstraint = static function (QueryException $exception): bool {
            $sqlState = $exception->errorInfo[0] ?? null;

            if ($sqlState !== null && str_starts_with((string) $sqlState, '23')) {
                return true;
            }

            return in_array($exception->getCode(), ['23000', '23505', '1062'], true);
        };

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($expectsApiJson) {
            if (config('app.debug') || ! $expectsApiJson($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Resource not found.',
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($expectsApiJson) {
            if (config('app.debug') || ! $expectsApiJson($request)) {
                return null;
            }

            return response()->json([
                'message' => 'This action is unauthorized.',
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (OfficerIssueConflict $exception, Request $request) use ($expectsApiJson) {
            if (config('app.debug') || ! $expectsApiJson($request)) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->code,
            ], $exception->status);
        });

        // Officer workflow conflicts (assignee, district, terminal assign, duplicate
        // resolution) are thrown as OfficerIssueConflict and rendered above. Only
        // integrity races on officer_issue_resolutions.issue_id are mapped here.
        $isOfficerResolutionIssueIdViolation = static function (QueryException $exception): bool {
            $message = strtolower($exception->getMessage());

            if (str_contains($message, 'officer_issue_resolutions_issue_id_unique')) {
                return true;
            }

            return str_contains($message, 'officer_issue_resolutions')
                && str_contains($message, 'issue_id');
        };

        $exceptions->render(function (QueryException $exception, Request $request) use ($expectsApiJson, $isIntegrityConstraint, $isOfficerResolutionIssueIdViolation) {
            if (config('app.debug') || ! $expectsApiJson($request)) {
                return null;
            }

            // Map duplicate officer_issue_resolutions.issue_id to structured 409
            // (OfficerIssueConflict also handles this; this covers race paths).
            if ($isIntegrityConstraint($exception) && $isOfficerResolutionIssueIdViolation($exception)) {
                return response()->json([
                    'message' => 'An officer resolution already exists for this issue.',
                    'code' => 'officer_resolution_exists',
                ], Response::HTTP_CONFLICT);
            }

            Log::error('Database query exception on API route.', [
                'exception' => $exception,
                'sql' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
            ]);

            $conflict = $isIntegrityConstraint($exception);

            return response()->json([
                'message' => $conflict
                    ? 'The request could not be completed due to a conflict with existing data.'
                    : 'Server error.',
            ], $conflict ? Response::HTTP_CONFLICT : Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($expectsApiJson) {
            if (config('app.debug') || ! $expectsApiJson($request)) {
                return null;
            }

            if (
                $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof AuthorizationException
                || $exception instanceof OfficerIssueConflict
                || $exception instanceof QueryException
                || $exception instanceof HttpExceptionInterface
            ) {
                return null;
            }

            return response()->json([
                'message' => 'Server error.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
