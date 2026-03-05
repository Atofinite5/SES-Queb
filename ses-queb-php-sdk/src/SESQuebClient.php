<?php

namespace BhargavKalambhe\SESQuebSDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class SESQuebClient
{
    private Client $httpClient;
    private string $apiUrl;
    private ?string $authToken = null;

    /**
     * Create a new SES-Queb client instance
     *
     * @param string $apiUrl Base URL of the SES-Queb API
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(
        string $apiUrl = 'https://ses-queb-api.render.com/api/v1',
        int $timeout = 30
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');

        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => $timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * Set authorization token for API requests
     */
    public function setAuthToken(string $token): self
    {
        $this->authToken = $token;
        return $this;
    }

    /**
     * Remove authorization token
     */
    public function removeAuthToken(): self
    {
        $this->authToken = null;
        return $this;
    }

    /**
     * Get request headers with auth if set
     */
    private function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'ses-queb-php-sdk/1.0.0',
        ];

        if ($this->authToken) {
            $headers['Authorization'] = "Bearer {$this->authToken}";
        }

        return $headers;
    }

    /**
     * Make HTTP request
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $options = ['headers' => $this->getHeaders()];

            if (!empty($data)) {
                if ($method === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = $this->httpClient->request($method, $endpoint, $options);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                throw new SESQuebException(
                    $body['message'] ?? "HTTP {$statusCode}",
                    $statusCode,
                    $body['errors'] ?? null
                );
            }

            return $body ?? [];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;
            $message = $e->getMessage();

            throw new SESQuebException($message, $statusCode);
        } catch (GuzzleException $e) {
            throw new SESQuebException("Request failed: {$e->getMessage()}");
        }
    }

    // =====================
    // TEMPLATE METHODS
    // =====================

    /**
     * Get all available templates
     */
    public function getTemplates(): array
    {
        return $this->request('GET', '/templates');
    }

    /**
     * Get a specific template by ID
     */
    public function getTemplate(int $templateId): array
    {
        return $this->request('GET', "/templates/{$templateId}");
    }

    // =====================
    // SCAFFOLD METHODS
    // =====================

    /**
     * Create a new scaffold job
     *
     * @param int $templateId Template ID to use
     * @param string $name Project name
     * @param array $config Project configuration
     *
     * @return array Scaffold job data
     */
    public function scaffold(int $templateId, string $name, array $config = []): array
    {
        return $this->request('POST', '/scaffold', [
            'template_id' => $templateId,
            'name' => $name,
            'config' => $config,
        ]);
    }

    /**
     * Get scaffold job status
     */
    public function getScaffoldStatus(string $jobId): array
    {
        return $this->request('GET', "/scaffold/{$jobId}/status");
    }

    /**
     * Get scaffold download URL
     */
    public function downloadScaffold(string $jobId): string
    {
        return "{$this->apiUrl}/scaffold/{$jobId}/download";
    }

    /**
     * Wait for scaffold job to complete
     *
     * @param string $jobId Job ID
     * @param int $maxAttempts Maximum poll attempts
     * @param int $intervalMs Poll interval in milliseconds
     *
     * @return array Completed job data
     * @throws SESQuebException
     */
    public function waitForScaffold(
        string $jobId,
        int $maxAttempts = 60,
        int $intervalMs = 5000
    ): array {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $job = $this->getScaffoldStatus($jobId);

            if ($job['status'] === 'completed') {
                return $job;
            }

            if ($job['status'] === 'failed') {
                throw new SESQuebException("Scaffold job {$jobId} failed");
            }

            $attempts++;
            usleep($intervalMs * 1000);
        }

        throw new SESQuebException(
            "Scaffold job {$jobId} timed out after {$maxAttempts} attempts"
        );
    }

    // =====================
    // AUDIT METHODS
    // =====================

    /**
     * Run security audit on a project
     *
     * @param string $projectPath Path to project directory
     * @param string $auditType Type of audit: 'quick', 'full', or 'detailed'
     *
     * @return array Audit report data
     */
    public function audit(string $projectPath, string $auditType = 'full'): array
    {
        return $this->request('POST', '/audit', [
            'project_path' => $projectPath,
            'audit_type' => $auditType,
        ]);
    }

    /**
     * Get audit report
     */
    public function getAuditReport(string $reportId): array
    {
        return $this->request('GET', "/audit/{$reportId}");
    }

    /**
     * List all audit reports
     */
    public function listAudits(int $page = 1, int $perPage = 15): array
    {
        return $this->request('GET', '/audits', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    // =====================
    // CONFIG METHODS
    // =====================

    /**
     * Get all saved configurations
     */
    public function getConfigs(int $page = 1, int $perPage = 15): array
    {
        return $this->request('GET', '/configs', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Save a new configuration
     */
    public function saveConfig(int $templateId, string $name, array $config): array
    {
        return $this->request('POST', '/configs', [
            'template_id' => $templateId,
            'name' => $name,
            'config' => $config,
        ]);
    }

    /**
     * Get a specific configuration
     */
    public function getConfig(int $configId): array
    {
        return $this->request('GET', "/configs/{$configId}");
    }

    /**
     * Delete a configuration
     */
    public function deleteConfig(int $configId): array
    {
        return $this->request('DELETE', "/configs/{$configId}");
    }

    // =====================
    // GITHUB METHODS
    // =====================

    /**
     * Connect GitHub account (OAuth)
     */
    public function connectGitHub(string $code): array
    {
        return $this->request('POST', '/github/connect', ['code' => $code]);
    }

    /**
     * Push project to GitHub
     */
    public function pushToGitHub(
        string $projectPath,
        string $repoName,
        bool $isPrivate = false
    ): array {
        return $this->request('POST', '/github/push', [
            'project_path' => $projectPath,
            'repo_name' => $repoName,
            'is_private' => $isPrivate,
        ]);
    }

    /**
     * List GitHub repositories
     */
    public function listGitHubRepositories(): array
    {
        return $this->request('GET', '/github/repositories');
    }

    // =====================
    // UTILITY METHODS
    // =====================

    /**
     * Check API health
     */
    public function health(): array
    {
        try {
            $this->getTemplates();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get API base URL
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
}
