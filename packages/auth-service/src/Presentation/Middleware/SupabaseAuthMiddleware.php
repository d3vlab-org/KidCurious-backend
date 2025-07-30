<?php

namespace KidsQaAi\AuthService\Presentation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KidsQaAi\AuthService\Application\Services\AuthService;

class SupabaseAuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'No authentication token provided'
            ], 401);
        }

        // Validate the token
        if (!$this->authService->validateToken($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Get user from token
        $user = $this->authService->authenticateByToken($token);
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User not found'
            ], 401);
        }

        // Add user and token to request for use in controllers
        $request->merge([
            'auth_user' => $user,
            'auth_token' => $token,
            'auth_context' => $this->authService->getUserContext($token)
        ]);

        // Add user to request attributes for easier access
        $request->attributes->set('user', $user);
        $request->attributes->set('token', $token);

        return $next($request);
    }

    /**
     * Extract JWT token from request
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header first
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Check for token in query parameters (fallback)
        $tokenFromQuery = $request->query('token');
        if ($tokenFromQuery) {
            return $tokenFromQuery;
        }

        // Check for token in request body (for POST requests)
        $tokenFromBody = $request->input('token');
        if ($tokenFromBody) {
            return $tokenFromBody;
        }

        return null;
    }
}
