<?php

namespace BhargavKalambhe\SESQuebSDK;

use Illuminate\Support\ServiceProvider;

class SESQuebServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ses-queb.php',
            'ses-queb'
        );

        $this->app->singleton(SESQuebClient::class, function ($app) {
            $config = $app['config']['ses-queb'];

            return new SESQuebClient(
                $config['api_url'] ?? 'https://ses-queb-api.render.com/api/v1',
                $config['timeout'] ?? 30
            );
        });

        // Alias for easy access
        $this->app->alias(SESQuebClient::class, 'ses-queb');
    }

    /**
     * Publish configuration
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ses-queb.php' => config_path('ses-queb.php'),
        ], 'ses-queb-config');
    }

    /**
     * Get provided services
     */
    public function provides(): array
    {
        return [SESQuebClient::class, 'ses-queb'];
    }
}
