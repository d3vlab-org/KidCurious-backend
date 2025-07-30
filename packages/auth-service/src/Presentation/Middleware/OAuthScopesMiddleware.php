<?php

namespace KidsQaAi\AuthService\Presentation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KidsQaAi\AuthService\Application\Services\AuthService;

class OAuthScopesMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$requiredScopes
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        // Check if user is authenticated (should be done by SupabaseAuthMiddleware first)
        $user = $request->attributes->get('user');
        $token = $request->attributes->get('token');

        if (!$user || !$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        // If no scopes required, allow access
        if (empty($requiredScopes)) {
            return $next($request);
        }

        // Check if user has required scopes
        if (!$this->authService->userHasScopes($user, $requiredScopes)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions',
                'required_scopes' => $requiredScopes,
                'user_scopes' => $this->authService->getScopesFromToken($token)
            ], 403);
        }

        return $next($request);
    }
}
