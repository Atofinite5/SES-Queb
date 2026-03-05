<?php

namespace App\Services\GitHub;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class GitHubService
{
    private const API_BASE_URL = 'https://api.github.com';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => 30,
        ]);
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        try {
            $response = $this->client->post('/login/oauth/access_token', [
                'json' => [
                    'client_id' => config('securescaffold.github.client_id'),
                    'client_secret' => config('securescaffold.github.client_secret'),
                    'code' => $code,
                ],
                'headers' => ['Accept' => 'application/json'],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('GitHub token exchange failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get authenticated user info.
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->client->get('/user', [
                'headers' => ['Authorization' => "Bearer $accessToken"],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('Failed to fetch user info', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a new repository.
     */
    public function createRepository(string $name, array $options = []): array
    {
        try {
            $response = $this->client->post('/user/repos', [
                'json' => array_merge([
                    'name' => $name,
                    'private' => $options['is_private'] ?? false,
                    'description' => $options['description'] ?? '',
                    'auto_init' => true,
                ], $options),
                'headers' => ['Authorization' => "Bearer {$options['token']}"],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('Failed to create repository', ['name' => $name, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Push code to repository.
     */
    public function pushProject(string $accessToken, string $repoName, string $jobId, array $options = []): array
    {
        try {
            $repo = $this->createRepository($repoName, array_merge($options, ['token' => $accessToken]));

            Log::info('Repository created', ['repo_name' => $repoName, 'url' => $repo['html_url']]);

            return [
                'repository_url' => $repo['html_url'],
                'repository_name' => $repo['name'],
                'owner' => $repo['owner']['login'],
            ];
        } catch (Exception $e) {
            Log::error('Failed to push project', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * List user repositories.
     */
    public function listRepositories(string $accessToken, int $perPage = 30): array
    {
        try {
            $response = $this->client->get('/user/repos', [
                'query' => ['per_page' => $perPage, 'sort' => 'updated'],
                'headers' => ['Authorization' => "Bearer $accessToken"],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('Failed to fetch repositories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Refresh access token.
     */
    public function refreshToken($connection): void
    {
        if (!$connection->refresh_token) {
            throw new Exception('No refresh token available');
        }

        try {
            $data = $this->exchangeCodeForToken($connection->refresh_token);
            $connection->update([
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            Log::info('GitHub token refreshed');
        } catch (Exception $e) {
            Log::error('Failed to refresh token', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
