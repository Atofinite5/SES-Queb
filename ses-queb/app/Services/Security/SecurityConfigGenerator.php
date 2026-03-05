<?php

namespace App\Services\Security;

class SecurityConfigGenerator
{
    /**
     * Generate security configurations.
     */
    public function generate(array $config): array
    {
        return [
            'headers' => $this->generateSecurityHeaders(),
            'csp' => $this->generateCSP($config),
            'cors' => $this->generateCORSPolicy($config),
            'rateLimit' => $this->generateRateLimit($config),
        ];
    }

    /**
     * Generate security headers.
     */
    protected function generateSecurityHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];
    }

    /**
     * Generate Content Security Policy.
     */
    protected function generateCSP(array $config): string
    {
        $directives = [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline'",
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' data:",
            'connect-src' => "'self'",
            'frame-ancestors' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
        ];

        return implode('; ', array_map(
            fn ($key, $value) => "$key $value",
            array_keys($directives),
            array_values($directives)
        ));
    }

    /**
     * Generate CORS policy.
     */
    protected function generateCORSPolicy(array $config): array
    {
        return [
            'allowed_origins' => [getenv('APP_URL') ?: 'http://localhost:3000'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
            'exposed_headers' => ['X-Total-Count'],
            'max_age' => 3600,
            'supports_credentials' => true,
        ];
    }

    /**
     * Generate rate limit configuration.
     */
    protected function generateRateLimit(array $config): array
    {
        return [
            'api' => [
                'requests' => 100,
                'period' => 'minute',
            ],
            'auth' => [
                'requests' => 5,
                'period' => 'minute',
            ],
            'audit' => [
                'requests' => 10,
                'period' => 'minute',
            ],
        ];
    }
}
