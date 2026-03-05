# SES-Queb PHP SDK

Official PHP/Laravel SDK for the SES-Queb API.

Scaffold secure Node.js/React/Vue projects and audit existing projects for vulnerabilities, license compliance, and security misconfigurations - all from your Laravel application.

---

## 📦 Installation

Install via Composer:

```bash
composer require bhargavkalambhe/ses-queb-sdk
```

---

## ⚙️ Configuration

### 1. Register Service Provider (Laravel < 11)

Add to `config/app.php`:

```php
'providers' => [
    // ...
    BhargavKalambhe\SESQuebSDK\SESQuebServiceProvider::class,
],

'aliases' => [
    // ...
    'SESQueb' => BhargavKalambhe\SESQuebSDK\SESQuebClient::class,
],
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=ses-queb-config
```

### 3. Set Environment Variables

Add to `.env`:

```env
SES_QUEB_API_URL=https://ses-queb-api.render.com/api/v1
SES_QUEB_TIMEOUT=30
SES_QUEB_AUTH_TOKEN=  # Optional, leave empty for public API
```

---

## 🚀 Quick Start

### Using Laravel Container

```php
<?php

use BhargavKalambhe\SESQuebSDK\SESQuebClient;

class ScaffoldController extends Controller
{
    public function store(Request $request, SESQuebClient $client)
    {
        // Create scaffold job
        $job = $client->scaffold(1, 'my-app', [
            'typescript' => true,
            'linting' => true,
        ]);

        return response()->json($job);
    }
}
```

### Direct Instantiation

```php
use BhargavKalambhe\SESQuebSDK\SESQuebClient;

$client = new SESQuebClient('https://ses-queb-api.render.com/api/v1', 30);

// Create project
$job = $client->scaffold(1, 'my-app', ['typescript' => true]);
```

---

## 📖 API Reference

### Templates

```php
// Get all templates
$templates = $client->getTemplates();
// [
//   ['id' => 1, 'name' => 'React', 'framework' => 'react'],
//   ['id' => 2, 'name' => 'Vue', 'framework' => 'vue'],
// ]

// Get specific template
$template = $client->getTemplate(1);
```

### Scaffold

```php
// Create scaffold job
$job = $client->scaffold(
    templateId: 1,
    name: 'my-react-app',
    config: [
        'typescript' => true,
        'linting' => true,
        'prettier' => true,
    ]
);
// ['id' => 'job-123', 'status' => 'pending', ...]

// Check job status
$status = $client->getScaffoldStatus('job-123');
// ['id' => 'job-123', 'status' => 'completed', ...]

// Wait for completion (polls every 5 seconds)
$completed = $client->waitForScaffold('job-123', maxAttempts: 60, intervalMs: 5000);

// Get download URL
$url = $client->downloadScaffold('job-123');
// 'https://ses-queb-api.render.com/api/v1/scaffold/job-123/download'
```

### Audit

```php
// Run security audit
$report = $client->audit(
    projectPath: '/path/to/project',
    auditType: 'full'  // 'quick', 'full', or 'detailed'
);

// Get audit report
$report = $client->getAuditReport('report-123');

// List all audits
$audits = $client->listAudits(page: 1, perPage: 15);
```

### Configurations

```php
// Save configuration
$config = $client->saveConfig(
    templateId: 1,
    name: 'React + TypeScript + Lint',
    config: [
        'typescript' => true,
        'linting' => true,
    ]
);

// Get all configs
$configs = $client->getConfigs();

// Get specific config
$config = $client->getConfig(1);

// Delete config
$client->deleteConfig(1);
```

### GitHub Integration

```php
// Connect GitHub (requires OAuth code)
$connection = $client->connectGitHub($githubOAuthCode);
// ['user' => 'username', 'expires_at' => '2025-03-10T...']

// Push project to GitHub
$result = $client->pushToGitHub(
    projectPath: '/path/to/project',
    repoName: 'my-app',
    isPrivate: false
);
// ['repo_url' => 'https://github.com/username/my-app', ...]

// List repositories
$repos = $client->listGitHubRepositories();
```

---

## 🎯 Usage Examples

### Example 1: Generate & Audit

```php
<?php

namespace App\Http\Controllers;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function scaffold(Request $request, SESQuebClient $client)
    {
        // Generate React project
        $job = $client->scaffold(1, 'my-app', [
            'typescript' => true,
            'linting' => true,
        ]);

        // Wait for completion
        $completed = $client->waitForScaffold($job['id']);

        // Get download URL
        $downloadUrl = $client->downloadScaffold($job['id']);

        return response()->json([
            'message' => 'Project created',
            'download_url' => $downloadUrl,
        ]);
    }

    public function audit(Request $request, SESQuebClient $client)
    {
        $report = $client->audit($request->input('path'), 'full');

        return response()->json([
            'vulnerabilities' => count($report['vulnerabilities']),
            'outdated' => count($report['outdated_packages']),
            'licenses' => count($report['licenses']),
        ]);
    }
}
```

### Example 2: Laravel Command

```php
<?php

namespace App\Console\Commands;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Illuminate\Console\Command;

class AuditProjectCommand extends Command
{
    protected $signature = 'project:audit {path}';
    protected $description = 'Audit a project for vulnerabilities';

    public function handle(SESQuebClient $client)
    {
        $path = $this->argument('path');

        $this->info('🔒 Running security audit...');

        $report = $client->audit($path, 'full');

        $this->info("\n📋 Audit Report");
        $this->line("🚨 Vulnerabilities: " . count($report['vulnerabilities']));
        $this->line("⬆️ Outdated: " . count($report['outdated_packages']));
        $this->line("📜 Licenses: " . count($report['licenses']));

        foreach ($report['vulnerabilities'] as $vuln) {
            $this->error("[{$vuln['severity']}] {$vuln['package']}: {$vuln['description']}");
        }
    }
}
```

### Example 3: Middleware Integration

```php
<?php

namespace App\Http\Middleware;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Closure;
use Illuminate\Http\Request;

class CheckProjectSecurity
{
    public function handle(Request $request, Closure $next, SESQuebClient $client)
    {
        // Auto-audit on specific routes
        if ($request->route()->getName() === 'projects.store') {
            $projectPath = $request->input('path');

            $report = $client->audit($projectPath, 'quick');

            if (count($report['vulnerabilities']) > 0) {
                return response()->json([
                    'error' => 'Project has security vulnerabilities',
                    'details' => $report['vulnerabilities']
                ], 422);
            }
        }

        return $next($request);
    }
}
```

---

## 🔐 Authentication

Set auth token in `.env`:

```env
SES_QUEB_AUTH_TOKEN=your-api-token
```

Or set it programmatically:

```php
$client = new SESQuebClient();
$client->setAuthToken('your-api-token');
```

---

## ⚠️ Error Handling

```php
use BhargavKalambhe\SESQuebSDK\SESQuebException;

try {
    $template = $client->getTemplate(999);
} catch (SESQuebException $e) {
    $message = $e->getMessage();          // Error message
    $code = $e->getCode();                // HTTP status code

    if ($e->hasErrors()) {
        $errors = $e->getErrors();        // Validation errors
    }
}
```

---

## 🧪 Testing

Use mock in tests:

```php
<?php

namespace Tests;

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use Mockery;

class ProjectTest extends TestCase
{
    public function test_scaffold_project()
    {
        $mock = Mockery::mock(SESQuebClient::class);

        $mock->shouldReceive('scaffold')
            ->with(1, 'test-app', Mockery::any())
            ->andReturn(['id' => 'job-123', 'status' => 'pending']);

        $this->app->instance(SESQuebClient::class, $mock);

        $response = $this->post('/api/scaffold', [
            'template_id' => 1,
            'name' => 'test-app',
        ]);

        $response->assertOk();
    }
}
```

---

## 📊 Configuration Reference

```php
// config/ses-queb.php

return [
    'api_url' => env('SES_QUEB_API_URL', 'https://ses-queb-api.render.com/api/v1'),
    'timeout' => env('SES_QUEB_TIMEOUT', 30),
    'auth_token' => env('SES_QUEB_AUTH_TOKEN', null),
];
```

---

## 🌐 Use in Other Laravel Projects

### Step 1: Install Package

```bash
composer require bhargavkalambhe/ses-queb-sdk
```

### Step 2: Setup Config

```bash
php artisan vendor:publish --tag=ses-queb-config
```

### Step 3: Add to .env

```env
SES_QUEB_API_URL=https://your-deployed-api.com/api/v1
```

### Step 4: Use in Code

```php
use BhargavKalambhe\SESQuebSDK\SESQuebClient;

class MyController extends Controller
{
    public function action(SESQuebClient $client)
    {
        $templates = $client->getTemplates();
        // ... your logic
    }
}
```

---

## 🚀 Publishing to Packagist

```bash
# Tag release
git tag v1.0.0
git push origin v1.0.0

# Submit to packagist.org
# Once published, developers can: composer require bhargavkalambhe/ses-queb-sdk
```

---

## 📄 License

MIT - See LICENSE file for details

---

## 🤝 Contributing

Contributions welcome! Please open an issue or pull request.

---

## 📞 Support

- **Issues:** [GitHub Issues](https://github.com/bhargavkalambhe/ses-queb-php-sdk/issues)
- **API Docs:** [SES-Queb Docs](https://github.com/bhargavkalambhe/ses-queb)

---

**Happy scaffolding with Laravel! 🎉**
