<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class AIFixAdvisor
{
    private string $provider;
    private string $apiKey;

    public function __construct()
    {
        $this->provider = config('securescaffold.ai.provider', 'openai');
        $this->apiKey = config('securescaffold.ai.api_key', '');
    }

    /**
     * Get AI-powered fix suggestions for vulnerabilities.
     */
    public function getSuggestions(array $vulnerability): array
    {
        if (!config('securescaffold.ai.enabled', false) || !$this->apiKey) {
            return $this->getStaticSuggestions($vulnerability);
        }

        try {
            return $this->queryAI($vulnerability, 'suggestions');
        } catch (Exception $e) {
            Log::error('AI suggestions failed, using static fallback', ['error' => $e->getMessage()]);
            return $this->getStaticSuggestions($vulnerability);
        }
    }

    /**
     * Generate fix code snippet.
     */
    public function generateFix(array $vulnerability): string
    {
        if (!config('securescaffold.ai.enabled', false) || !$this->apiKey) {
            return $this->getStaticFix($vulnerability);
        }

        try {
            $result = $this->queryAI($vulnerability, 'fix');
            return $result['code'] ?? $this->getStaticFix($vulnerability);
        } catch (Exception $e) {
            Log::error('AI fix generation failed, using static fallback', ['error' => $e->getMessage()]);
            return $this->getStaticFix($vulnerability);
        }
    }

    /**
     * Query AI provider for suggestions.
     */
    private function queryAI(array $vulnerability, string $mode): array
    {
        $prompt = $this->buildPrompt($vulnerability, $mode);

        if ($this->provider === 'openai') {
            return $this->queryOpenAI($prompt);
        }

        throw new Exception("Unsupported AI provider: {$this->provider}");
    }

    /**
     * Query OpenAI API.
     */
    private function queryOpenAI(string $prompt): array
    {
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a security expert helping fix vulnerabilities.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
            ]);

        if ($response->failed()) {
            throw new Exception('OpenAI API error: ' . $response->status());
        }

        $content = $response->json('choices.0.message.content');
        return ['code' => $content, 'suggestions' => [$content]];
    }

    /**
     * Build prompt for AI.
     */
    private function buildPrompt(array $vulnerability, string $mode): string
    {
        $type = $vulnerability['type'] ?? 'unknown';
        $message = $vulnerability['message'] ?? 'Vulnerability detected';

        if ($mode === 'fix') {
            return "Fix this $type vulnerability: $message. Provide only the fixed code.";
        }

        return "Provide fix suggestions for this $type vulnerability: $message. List 3 actionable recommendations.";
    }

    /**
     * Get static fallback suggestions.
     */
    private function getStaticSuggestions(array $vulnerability): array
    {
        $type = $vulnerability['type'] ?? 'unknown';

        $suggestions = [
            'npm_vulnerability' => [
                'Update the package to the latest patched version',
                'Review the vulnerability details and assess impact',
                'Consider using an alternative package if available',
            ],
            'outdated_dependency' => [
                'Run npm update to upgrade all dependencies',
                'Test thoroughly after upgrading',
                'Review breaking changes in the changelog',
            ],
            'hardcoded_secrets' => [
                'Move secrets to environment variables',
                'Add .env to .gitignore',
                'Use a secrets management service',
            ],
            'vulnerable_patterns' => [
                'Avoid using eval() - use safer alternatives',
                'Use .textContent instead of .innerHTML for dynamic content',
                'Validate and sanitize all user input',
            ],
        ];

        return $suggestions[$type] ?? [
            'Review the vulnerability details',
            'Check the package documentation',
            'Apply the recommended security fix',
        ];
    }

    /**
     * Get static fallback fix code.
     */
    private function getStaticFix(array $vulnerability): string
    {
        $type = $vulnerability['type'] ?? 'unknown';

        $fixes = [
            'hardcoded_secrets' => <<<'CODE'
// Before: Hardcoded secret
const API_KEY = 'sk-xxxxxxxxxxxxx';

// After: Use environment variable
const API_KEY = process.env.API_KEY;
CODE,
            'vulnerable_patterns' => <<<'CODE'
// Before: Unsafe innerHTML
element.innerHTML = userInput;

// After: Safe textContent
element.textContent = userInput;
CODE,
            'eval_usage' => <<<'CODE'
// Before: Using eval
eval(userCode);

// After: Use Function constructor or safer alternative
new Function(userCode)();
CODE,
        ];

        return $fixes[$type] ?? 'Please review the vulnerability details and consult security best practices.';
    }
}
