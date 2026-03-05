<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GitHubConnection;
use App\Services\GitHub\GitHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubController extends Controller
{
    public function __construct(
        private GitHubService $githubService
    ) {}

    /**
     * Connect GitHub account via OAuth.
     */
    public function connect(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);

            if (session('github_oauth_state') !== $validated['state']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OAuth state. Security validation failed.',
                ], 422);
            }

            $tokenData = $this->githubService->exchangeCodeForToken($validated['code']);

            if (!isset($tokenData['access_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to obtain GitHub access token',
                ], 422);
            }

            $userData = $this->githubService->getUserInfo($tokenData['access_token']);

            $connection = GitHubConnection::updateOrCreate(
                ['github_username' => $userData['login']],
                [
                    'github_username' => $userData['login'],
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'expires_at' => $tokenData['expires_at'] ?? null,
                ]
            );

            Log::info('GitHub account connected', [
                'github_username' => $userData['login'],
                'connection_id' => $connection->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'GitHub account connected successfully',
                'data' => [
                    'username' => $connection->github_username,
                    'connected_at' => $connection->updated_at,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('GitHub connection failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect GitHub account',
            ], 500);
        }
    }

    /**
     * Push scaffolded project to GitHub.
     */
    public function push(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'job_id' => 'required|string|exists:scaffold_jobs,job_id',
                'repo_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9._-]+$/',
                'is_private' => 'boolean|nullable',
                'description' => 'string|nullable|max:500',
            ], [
                'repo_name.regex' => 'Repository name can only contain letters, numbers, dots, hyphens, and underscores',
            ]);

            $connection = GitHubConnection::first();
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'GitHub account not connected. Please connect first.',
                ], 422);
            }

            if ($connection->isTokenExpired()) {
                $this->githubService->refreshToken($connection);
            }

            $result = $this->githubService->pushProject(
                $connection->access_token,
                $validated['repo_name'],
                $validated['job_id'],
                [
                    'is_private' => $validated['is_private'] ?? false,
                    'description' => $validated['description'] ?? '',
                ]
            );

            Log::info('Project pushed to GitHub', [
                'job_id' => $validated['job_id'],
                'repo_name' => $validated['repo_name'],
                'username' => $connection->github_username,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Project pushed to GitHub successfully',
                'data' => $result,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to push to GitHub', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to push to GitHub',
            ], 500);
        }
    }

    /**
     * List user repositories.
     */
    public function repositories(Request $request): JsonResponse
    {
        try {
            $connection = GitHubConnection::first();
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'GitHub account not connected',
                ], 422);
            }

            if ($connection->isTokenExpired()) {
                $this->githubService->refreshToken($connection);
            }

            $repos = $this->githubService->listRepositories(
                $connection->access_token,
                $request->query('per_page', 30)
            );

            return response()->json([
                'success' => true,
                'data' => $repos,
                'count' => count($repos),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch repositories', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch repositories',
            ], 500);
        }
    }
}
