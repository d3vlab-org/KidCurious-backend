<?php

namespace KidsQaAi\AuthService\Infrastructure\Repositories;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use KidsQaAi\AuthService\Domain\Contracts\AuthRepositoryInterface;
use KidsQaAi\AuthService\Domain\Contracts\JwtServiceInterface;
use KidsQaAi\AuthService\Domain\Entities\User;

class SupabaseAuthRepository implements AuthRepositoryInterface
{
    private string $supabaseUrl;
    private string $supabaseAnonKey;
    private string $supabaseServiceKey;
    private JwtServiceInterface $jwtService;

    public function __construct(JwtServiceInterface $jwtService)
    {
        $this->supabaseUrl = config('auth-service.supabase.url');
        $this->supabaseAnonKey = config('auth-service.supabase.anon_key');
        $this->supabaseServiceKey = config('auth-service.supabase.service_role_key');
        $this->jwtService = $jwtService;
    }

    /**
     * Register a new user with email and password
     */
    public function register(string $email, string $password, array $metadata = []): array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->supabaseUrl}/auth/v1/signup", [
                'email' => $email,
                'password' => $password,
                'data' => $metadata,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store user in local database
                if (isset($data['user'])) {
                    DB::table('users')->updateOrInsert(
                        ['id' => $data['user']['id']],
                        [
                            'email' => $data['user']['email'],
                            'user_metadata' => json_encode($data['user']['user_metadata'] ?? []),
                            'app_metadata' => json_encode($data['user']['app_metadata'] ?? []),
                            'created_at' => $data['user']['created_at'],
                            'updated_at' => $data['user']['updated_at'] ?? now(),
                        ]
                    );
                }

                return [
                    'success' => true,
                    'user' => $data['user'] ?? null,
                    'session' => $data['session'] ?? null,
                    'access_token' => $data['access_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['msg'] ?? 'Registration failed',
            ];
        } catch (\Exception $e) {
            \Log::error('Registration failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Login user with email and password
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->supabaseUrl}/auth/v1/token?grant_type=password", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update user in local database
                if (isset($data['user'])) {
                    DB::table('users')->updateOrInsert(
                        ['id' => $data['user']['id']],
                        [
                            'email' => $data['user']['email'],
                            'user_metadata' => json_encode($data['user']['user_metadata'] ?? []),
                            'app_metadata' => json_encode($data['user']['app_metadata'] ?? []),
                            'created_at' => $data['user']['created_at'],
                            'updated_at' => $data['user']['updated_at'] ?? now(),
                        ]
                    );
                }

                return [
                    'success' => true,
                    'user' => $data['user'] ?? null,
                    'session' => $data['session'] ?? null,
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error_description'] ?? 'Login failed',
            ];
        } catch (\Exception $e) {
            \Log::error('Login failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Logout user by invalidating the token
     */
    public function logout(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAnonKey,
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->post("{$this->supabaseUrl}/auth/v1/logout");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Logged out successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['msg'] ?? 'Logout failed',
            ];
        } catch (\Exception $e) {
            \Log::error('Logout failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Logout failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate JWT token and return user information
     */
    public function validateToken(string $token): ?User
    {
        if (!$this->jwtService->validateToken($token)) {
            return null;
        }

        $userId = $this->jwtService->getUserIdFromToken($token);
        if (!$userId) {
            return null;
        }

        return $this->getUserById($userId);
    }

    /**
     * Get user by ID
     */
    public function getUserById(string $userId): ?User
    {
        try {
            // First try to get from local database
            $userData = DB::table('users')->where('id', $userId)->first();

            if ($userData) {
                return new User(
                    $userData->id,
                    $userData->email,
                    json_decode($userData->user_metadata ?? '{}', true),
                    json_decode($userData->app_metadata ?? '{}', true),
                    $userData->created_at,
                    $userData->updated_at
                );
            }

            // If not found locally, try Supabase Auth API
            $response = Http::withHeaders([
                'apikey' => $this->supabaseServiceKey,
                'Authorization' => "Bearer {$this->supabaseServiceKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->supabaseUrl}/auth/v1/admin/users/{$userId}");

            if ($response->successful()) {
                $data = $response->json();

                // Store in local database for future queries
                DB::table('users')->updateOrInsert(
                    ['id' => $data['id']],
                    [
                        'email' => $data['email'],
                        'user_metadata' => json_encode($data['user_metadata'] ?? []),
                        'app_metadata' => json_encode($data['app_metadata'] ?? []),
                        'created_at' => $data['created_at'],
                        'updated_at' => $data['updated_at'] ?? now(),
                    ]
                );

                return new User(
                    $data['id'],
                    $data['email'],
                    $data['user_metadata'] ?? [],
                    $data['app_metadata'] ?? [],
                    $data['created_at'],
                    $data['updated_at']
                );
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to get user by ID', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if user has required OAuth2 scopes
     */
    public function hasScopes(User $user, array $requiredScopes): bool
    {
        $userScopes = $user->getAppMetadata()['scopes'] ?? [];

        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $userScopes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new child user
     */
    public function createChild(array $childData, string $parentId): User
    {
        try {
            // Create user in Supabase Auth
            $response = Http::withHeaders([
                'apikey' => $this->supabaseServiceKey,
                'Authorization' => "Bearer {$this->supabaseServiceKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->supabaseUrl}/auth/v1/admin/users", [
                'email' => $childData['email'],
                'password' => $childData['password'] ?? \Str::random(16),
                'user_metadata' => array_merge($childData['user_metadata'] ?? [], [
                    'user_type' => 'child',
                    'parent_id' => $parentId,
                ]),
                'app_metadata' => array_merge($childData['app_metadata'] ?? [], [
                    'scopes' => ['child'],
                ]),
                'email_confirm' => true,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to create child user in Supabase');
            }

            $data = $response->json();

            // Store in local database
            DB::table('users')->insert([
                'id' => $data['id'],
                'email' => $data['email'],
                'user_metadata' => json_encode($data['user_metadata']),
                'app_metadata' => json_encode($data['app_metadata']),
                'created_at' => $data['created_at'],
                'updated_at' => now(),
            ]);

            return new User(
                $data['id'],
                $data['email'],
                $data['user_metadata'],
                $data['app_metadata'],
                $data['created_at'],
                $data['updated_at']
            );
        } catch (\Exception $e) {
            \Log::error('Failed to create child user', [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new parent user
     */
    public function createParent(array $parentData): User
    {
        try {
            // Create user in Supabase Auth
            $response = Http::withHeaders([
                'apikey' => $this->supabaseServiceKey,
                'Authorization' => "Bearer {$this->supabaseServiceKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->supabaseUrl}/auth/v1/admin/users", [
                'email' => $parentData['email'],
                'password' => $parentData['password'],
                'user_metadata' => array_merge($parentData['user_metadata'] ?? [], [
                    'user_type' => 'parent',
                ]),
                'app_metadata' => array_merge($parentData['app_metadata'] ?? [], [
                    'scopes' => ['parent', 'child_management'],
                ]),
                'email_confirm' => true,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to create parent user in Supabase');
            }

            $data = $response->json();

            // Store in local database
            DB::table('users')->insert([
                'id' => $data['id'],
                'email' => $data['email'],
                'user_metadata' => json_encode($data['user_metadata']),
                'app_metadata' => json_encode($data['app_metadata']),
                'created_at' => $data['created_at'],
                'updated_at' => now(),
            ]);

            return new User(
                $data['id'],
                $data['email'],
                $data['user_metadata'],
                $data['app_metadata'],
                $data['created_at'],
                $data['updated_at']
            );
        } catch (\Exception $e) {
            \Log::error('Failed to create parent user', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update user information
     */
    public function updateUser(string $userId, array $userData): User
    {
        try {
            // Update in Supabase Auth
            $response = Http::withHeaders([
                'apikey' => $this->supabaseServiceKey,
                'Authorization' => "Bearer {$this->supabaseServiceKey}",
                'Content-Type' => 'application/json',
            ])->put("{$this->supabaseUrl}/auth/v1/admin/users/{$userId}", $userData);

            if (!$response->successful()) {
                throw new \Exception('Failed to update user in Supabase');
            }

            $data = $response->json();

            // Update in local database
            DB::table('users')->where('id', $userId)->update([
                'email' => $data['email'],
                'user_metadata' => json_encode($data['user_metadata']),
                'app_metadata' => json_encode($data['app_metadata']),
                'updated_at' => now(),
            ]);

            return new User(
                $data['id'],
                $data['email'],
                $data['user_metadata'],
                $data['app_metadata'],
                $data['created_at'],
                $data['updated_at']
            );
        } catch (\Exception $e) {
            \Log::error('Failed to update user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId): bool
    {
        try {
            // Delete from Supabase Auth
            $response = Http::withHeaders([
                'apikey' => $this->supabaseServiceKey,
                'Authorization' => "Bearer {$this->supabaseServiceKey}",
            ])->delete("{$this->supabaseUrl}/auth/v1/admin/users/{$userId}");

            if ($response->successful()) {
                // Delete from local database
                DB::table('users')->where('id', $userId)->delete();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Failed to delete user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get children for a parent
     */
    public function getChildrenForParent(string $parentId): array
    {
        try {
            $children = DB::table('users')
                ->whereRaw("JSON_EXTRACT(user_metadata, '$.parent_id') = ?", [$parentId])
                ->get();

            return $children->map(function ($child) {
                return new User(
                    $child->id,
                    $child->email,
                    json_decode($child->user_metadata, true),
                    json_decode($child->app_metadata, true),
                    $child->created_at,
                    $child->updated_at
                );
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Failed to get children for parent', [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if user is a child
     */
    public function isChild(User $user): bool
    {
        $userMetadata = $user->getUserMetadata();
        return ($userMetadata['user_type'] ?? '') === 'child';
    }

    /**
     * Check if user is a parent
     */
    public function isParent(User $user): bool
    {
        $userMetadata = $user->getUserMetadata();
        return ($userMetadata['user_type'] ?? '') === 'parent';
    }

    /**
     * Get parent for a child
     */
    public function getParentForChild(string $childId): ?User
    {
        try {
            $child = $this->getUserById($childId);
            if (!$child || !$this->isChild($child)) {
                return null;
            }

            $parentId = $child->getUserMetadata()['parent_id'] ?? null;
            if (!$parentId) {
                return null;
            }

            return $this->getUserById($parentId);
        } catch (\Exception $e) {
            \Log::error('Failed to get parent for child', [
                'child_id' => $childId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Rotate API keys for user
     */
    public function rotateApiKeys(string $userId): array
    {
        try {
            $newApiKey = \Str::random(32);
            $newSecretKey = \Str::random(64);

            $this->updateUser($userId, [
                'app_metadata' => [
                    'api_key' => $newApiKey,
                    'secret_key' => hash('sha256', $newSecretKey),
                    'keys_rotated_at' => now()->toISOString(),
                ]
            ]);

            return [
                'api_key' => $newApiKey,
                'secret_key' => $newSecretKey,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to rotate API keys', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
