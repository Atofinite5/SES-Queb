# 🌍 SES-Queb PHP SDK - Global Usage Guide

Use the SDK globally across **ALL your Laravel projects** on this machine.

---

## ✅ Setup Complete!

Your SDK is now available at:
```
~/composer-repos/ses-queb-sdk/
```

And registered in your global Composer config:
```
~/.composer/config.json
```

---

## 🚀 How to Use in ANY Laravel Project

### Step 1: Create or Open Your Laravel Project

```bash
# Create new project
composer create-project laravel/laravel my-project
cd my-project
```

### Step 2: Add SDK to composer.json

```json
{
  "require": {
    "bhargavkalambhe/ses-queb-sdk": "*"
  }
}
```

### Step 3: Install

```bash
composer install
```

Done! ✅

### Step 4: Publish Configuration

```bash
php artisan vendor:publish --tag=ses-queb-config
```

---

## 📖 Usage Examples

### Example 1: In a Controller

```php
<?php

namespace App\Http\Controllers;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function scaffold(Request $request, SESQuebClient $client)
    {
        // Create project
        $job = $client->scaffold(
            1,
            'my-app',
            ['typescript' => true, 'linting' => true]
        );

        return response()->json([
            'message' => 'Project generation started',
            'job_id' => $job['id'],
            'status' => $job['status']
        ]);
    }

    public function audit(Request $request, SESQuebClient $client)
    {
        // Run audit
        $report = $client->audit($request->input('path'), 'full');

        return response()->json([
            'vulnerabilities' => count($report['vulnerabilities']),
            'outdated' => count($report['outdated_packages']),
            'licenses' => count($report['licenses']),
        ]);
    }
}
```

### Example 2: In a Command

```php
<?php

namespace App\Console\Commands;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Illuminate\Console\Command;

class ScaffoldCommand extends Command
{
    protected $signature = 'scaffold:create {template} {name}';
    protected $description = 'Create a new scaffolded project';

    public function handle(SESQuebClient $client)
    {
        $template = $this->argument('template');
        $name = $this->argument('name');

        $this->info("📦 Creating {$name}...");

        $job = $client->scaffold($template, $name, [
            'typescript' => $this->confirm('Use TypeScript?'),
            'linting' => $this->confirm('Use Linting?'),
        ]);

        $this->info("✅ Job created: {$job['id']}");
        $this->info("Waiting for completion...");

        $completed = $client->waitForScaffold($job['id']);

        $downloadUrl = $client->downloadScaffold($job['id']);
        $this->line("📥 Download: {$downloadUrl}");
    }
}
```

Usage:
```bash
php artisan scaffold:create 1 my-app
# Output:
# 📦 Creating my-app...
# Use TypeScript? (yes/no) [no]: yes
# Use Linting? (yes/no) [no]: yes
# ✅ Job created: job-abc123
# Waiting for completion...
# 📥 Download: https://ses-queb-api.render.com/api/v1/scaffold/job-abc123/download
```

### Example 3: In a Service Class

```php
<?php

namespace App\Services;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use BhargavKalambhe\SESQuebSDK\SESQuebException;

class ProjectService
{
    public function __construct(private SESQuebClient $client) {}

    public function generateProject(array $data)
    {
        try {
            $job = $this->client->scaffold(
                $data['template_id'],
                $data['name'],
                $data['config'] ?? []
            );

            return [
                'success' => true,
                'job_id' => $job['id'],
                'download_url' => $this->client->downloadScaffold($job['id']),
            ];
        } catch (SESQuebException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function auditProject(string $path)
    {
        try {
            return $this->client->audit($path, 'full');
        } catch (SESQuebException $e) {
            throw $e;
        }
    }

    public function saveConfiguration(int $templateId, string $name, array $config)
    {
        return $this->client->saveConfig($templateId, $name, $config);
    }
}
```

---

## 🔧 Configuration

Edit `config/ses-queb.php` in your project:

```php
return [
    // API URL - use your deployed API
    'api_url' => env('SES_QUEB_API_URL', 'https://ses-queb-api.render.com/api/v1'),

    // Request timeout
    'timeout' => env('SES_QUEB_TIMEOUT', 30),

    // Optional auth token
    'auth_token' => env('SES_QUEB_AUTH_TOKEN', null),
];
```

Or set in `.env`:

```env
SES_QUEB_API_URL=https://your-api-instance.com/api/v1
SES_QUEB_TIMEOUT=30
SES_QUEB_AUTH_TOKEN=your-token-here
```

---

## 📋 All Available Methods

```php
// Templates
$client->getTemplates(): array
$client->getTemplate(int $templateId): array

// Scaffold
$client->scaffold(int $templateId, string $name, array $config): array
$client->getScaffoldStatus(string $jobId): array
$client->waitForScaffold(string $jobId, int $maxAttempts = 60, int $intervalMs = 5000): array
$client->downloadScaffold(string $jobId): string

// Audit
$client->audit(string $projectPath, string $auditType = 'full'): array
$client->getAuditReport(string $reportId): array
$client->listAudits(int $page = 1, int $perPage = 15): array

// Config
$client->getConfigs(int $page = 1, int $perPage = 15): array
$client->saveConfig(int $templateId, string $name, array $config): array
$client->getConfig(int $configId): array
$client->deleteConfig(int $configId): array

// GitHub
$client->connectGitHub(string $code): array
$client->pushToGitHub(string $projectPath, string $repoName, bool $isPrivate = false): array
$client->listGitHubRepositories(): array

// Utility
$client->health(): array
$client->setAuthToken(string $token): self
$client->removeAuthToken(): self
$client->getApiUrl(): string
```

---

## ⚠️ Error Handling

```php
use BhargavKalambhe\SESQuebSDK\SESQuebException;

try {
    $result = $client->scaffold(1, 'app', []);
} catch (SESQuebException $e) {
    $message = $e->getMessage();
    $statusCode = $e->getCode();

    if ($e->hasErrors()) {
        $validationErrors = $e->getErrors();
    }
}
```

---

## 🧪 Testing with Mockery

```php
<?php

namespace Tests\Feature;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Mockery;
use Tests\TestCase;

class ProjectScaffoldTest extends TestCase
{
    public function test_scaffold_creates_project()
    {
        $mock = Mockery::mock(SESQuebClient::class);

        $mock->shouldReceive('scaffold')
            ->with(1, 'test-app', Mockery::any())
            ->andReturn(['id' => 'job-123', 'status' => 'pending']);

        $this->app->instance(SESQuebClient::class, $mock);

        $response = $this->post('/api/scaffold', [
            'template_id' => 1,
            'name' => 'test-app',
            'config' => ['typescript' => true],
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('job_id', 'job-123');
    }
}
```

---

## 📂 Project Structure After Setup

```
my-laravel-project/
├── app/
│   ├── Console/Commands/
│   │   └── ScaffoldCommand.php
│   ├── Http/Controllers/
│   │   └── ProjectController.php
│   └── Services/
│       └── ProjectService.php
├── config/
│   └── ses-queb.php (published)
├── vendor/
│   └── bhargavkalambhe/
│       └── ses-queb-sdk (symlinked!)
├── composer.json
├── .env
└── .gitignore
```

---

## 🔄 Updating the SDK

When you update the SDK in `~/composer-repos/ses-queb-sdk/`:

```bash
# Simply update in your projects
cd my-project
composer update bhargavkalambhe/ses-queb-sdk
```

Since it's symlinked, changes are reflected immediately! ⚡

---

## 🎯 Quick Commands Reference

```bash
# In any Laravel project:

# Install SDK
composer require bhargavkalambhe/ses-queb-sdk

# Publish config
php artisan vendor:publish --tag=ses-queb-config

# Create a command to use it
php artisan make:command ScaffoldProject

# Create a controller to use it
php artisan make:controller ProjectController

# Create a service to use it
php artisan make:class Services/ProjectService
```

---

## 📊 SDK Location Reference

| Item | Location |
|------|----------|
| SDK Source | `~/composer-repos/ses-queb-sdk/` |
| Composer Config | `~/.composer/config.json` |
| SDK in Project | `vendor/bhargavkalambhe/ses-queb-sdk/` (symlinked) |
| Your Projects | Anywhere, any number |

---

## ✅ Verification

Check it's working:

```bash
cd ~/your-laravel-project

# Check vendor folder has symlink
ls -la vendor/bhargavkalambhe/ses-queb-sdk
# Should show: -> ../../../composer-repos/ses-queb-sdk

# Test in tinker
php artisan tinker
>>> $client = app(\BhargavKalambhe\SESQuebSDK\SESQuebClient::class)
>>> $client->getApiUrl()
```

---

## 🚀 Now You Can:

✅ Create scaffolds in any Laravel project
✅ Run audits from any Laravel project
✅ Manage configurations globally
✅ Keep everything synchronized
✅ Update SDK once, affects all projects

---

## 💡 Pro Tips

1. **Create a macro** for common operations:
```php
// In a service provider
\BhargavKalambhe\SESQuebSDK\SESQuebClient::macro('scaffoldWithDefaults', function(string $name) {
    return $this->scaffold(1, $name, [
        'typescript' => true,
        'linting' => true,
    ]);
});

// Use: $client->scaffoldWithDefaults('my-app');
```

2. **Cache results** for frequent calls:
```php
$templates = Cache::remember('ses-queb.templates', 3600, function() {
    return app(SESQuebClient::class)->getTemplates();
});
```

3. **Log all operations**:
```php
// In a middleware
Log::info('SES-Queb Operation', [
    'method' => 'scaffold',
    'template_id' => $templateId,
]);
```

---

## 🎉 You're All Set!

Use the SDK in **any Laravel project** on your machine now!

```bash
# Just add to composer.json:
{
  "require": {
    "bhargavkalambhe/ses-queb-sdk": "*"
  }
}

composer install
```

Done! 🚀
