# SES-Queb PHP SDK - Project Structure

Complete PHP/Laravel Client Library for SES-Queb API.

---

## 📁 Directory Structure

```
ses-queb-php-sdk/
├── src/
│   ├── SESQuebClient.php          ✅ Main client class (all API methods)
│   ├── SESQuebException.php        ✅ Custom exception handling
│   └── SESQuebServiceProvider.php  ✅ Laravel service provider
├── config/
│   └── ses-queb.php               ✅ Configuration template
├── examples/
│   └── basic-usage.php            ✅ Usage examples
├── composer.json                   ✅ Package configuration
├── package.json                    ⚠️ NOT USED (PHP only)
├── tsconfig.json                   ⚠️ NOT USED (PHP only)
├── README.md                       ✅ Full documentation
├── LICENSE                         ✅ MIT License
├── .gitignore                      ✅ Git ignore rules
└── STRUCTURE.md                    ✅ This file
```

---

## 📦 Package Contents

### Core Files

**`src/SESQuebClient.php`** (400+ lines)
- Main client class with all API methods
- Methods:
  - `getTemplates()`, `getTemplate()`
  - `scaffold()`, `getScaffoldStatus()`, `waitForScaffold()`, `downloadScaffold()`
  - `audit()`, `getAuditReport()`, `listAudits()`
  - `getConfigs()`, `saveConfig()`, `getConfig()`, `deleteConfig()`
  - `connectGitHub()`, `pushToGitHub()`, `listGitHubRepositories()`
  - `health()`
- Authentication support
- Error handling
- Chainable interface

**`src/SESQuebException.php`** (40 lines)
- Custom exception class
- Stores validation errors
- Error details access methods

**`src/SESQuebServiceProvider.php`** (45 lines)
- Laravel service provider
- Automatic dependency injection
- Configuration publishing
- Service container binding

### Configuration

**`config/ses-queb.php`**
- API URL configuration
- Timeout settings
- Authentication token
- Environment-based configuration

### Documentation

**`README.md`** (400+ lines)
- Installation instructions
- Configuration setup
- Complete API reference
- Usage examples (5+)
- Error handling
- Testing examples
- Testing with Mockery
- Publishing to Packagist

**`examples/basic-usage.php`** (200+ lines)
- List templates
- Scaffold project
- Audit project
- Save configuration
- Check health
- Authentication example

---

## 🚀 How to Use

### 1. Install Package

```bash
composer require bhargavkalambhe/ses-queb-sdk
```

### 2. Configure in Laravel

```bash
php artisan vendor:publish --tag=ses-queb-config
```

### 3. Set Environment

```env
SES_QUEB_API_URL=https://ses-queb-api.render.com/api/v1
SES_QUEB_TIMEOUT=30
```

### 4. Use in Code

```php
use BhargavKalambhe\SESQuebSDK\SESQuebClient;

// Inject via container
class MyController extends Controller {
    public function scaffold(SESQuebClient $client) {
        $job = $client->scaffold(1, 'my-app', ['typescript' => true]);
        return response()->json($job);
    }
}
```

---

## 📊 Features

✅ **All API Endpoints**
- Templates: Get all, get by ID
- Scaffold: Create, status, wait, download
- Audit: Run, get report, list
- Config: Get, save, delete
- GitHub: Connect, push, list repos

✅ **Laravel Integration**
- Service provider for auto-registration
- Configuration publishing
- Dependency injection support
- Environment-based config

✅ **Error Handling**
- Custom exception class
- Validation error tracking
- HTTP status codes
- Proper error messages

✅ **Authentication**
- Bearer token support
- Chainable interface
- Token management

✅ **Utilities**
- Health check
- Polling with `waitForScaffold()`
- Download URLs
- Configurable timeouts

---

## 🧪 Usage Patterns

### Pattern 1: Direct Use
```php
$client = new SESQuebClient('https://api.example.com', 30);
$templates = $client->getTemplates();
```

### Pattern 2: Laravel Container
```php
class MyController {
    public function action(SESQuebClient $client) {
        // Automatically injected
        return $client->getTemplates();
    }
}
```

### Pattern 3: With Auth
```php
$client = new SESQuebClient();
$client->setAuthToken('token-here');
$result = $client->scaffold(1, 'app', []);
```

### Pattern 4: Error Handling
```php
try {
    $result = $client->scaffold(1, 'app', []);
} catch (SESQuebException $e) {
    // Handle error
}
```

---

## 📥 Installation Methods

### Method 1: From GitHub
```bash
composer require bhargavkalambhe/ses-queb-php-sdk:dev-main
```

### Method 2: From Packagist (After Publishing)
```bash
composer require bhargavkalambhe/ses-queb-sdk
```

### Method 3: Local Testing
```bash
# Add to composer.json
{
  "repositories": [
    {
      "type": "path",
      "url": "../ses-queb-php-sdk"
    }
  ],
  "require": {
    "bhargavkalambhe/ses-queb-sdk": "*"
  }
}
```

---

## 🔧 Configuration Reference

```php
// config/ses-queb.php
return [
    'api_url' => env('SES_QUEB_API_URL', 'https://ses-queb-api.render.com/api/v1'),
    'timeout' => env('SES_QUEB_TIMEOUT', 30),
    'auth_token' => env('SES_QUEB_AUTH_TOKEN', null),
];
```

---

## 📚 API Methods Reference

### Templates
```php
$client->getTemplates(): array
$client->getTemplate(int $id): array
```

### Scaffold
```php
$client->scaffold(int $templateId, string $name, array $config): array
$client->getScaffoldStatus(string $jobId): array
$client->waitForScaffold(string $jobId, int $maxAttempts = 60, int $intervalMs = 5000): array
$client->downloadScaffold(string $jobId): string
```

### Audit
```php
$client->audit(string $projectPath, string $auditType = 'full'): array
$client->getAuditReport(string $reportId): array
$client->listAudits(int $page = 1, int $perPage = 15): array
```

### Config
```php
$client->getConfigs(int $page = 1, int $perPage = 15): array
$client->saveConfig(int $templateId, string $name, array $config): array
$client->getConfig(int $configId): array
$client->deleteConfig(int $configId): array
```

### GitHub
```php
$client->connectGitHub(string $code): array
$client->pushToGitHub(string $projectPath, string $repoName, bool $isPrivate): array
$client->listGitHubRepositories(): array
```

### Utility
```php
$client->health(): array
$client->setAuthToken(string $token): self
$client->removeAuthToken(): self
$client->getApiUrl(): string
```

---

## 🎯 Use Cases

1. **Internal Laravel Project**
   - Use SDK to scaffold new projects
   - Run audits on codebases
   - Manage GitHub repos

2. **Admin Dashboard**
   - Create projects via web interface
   - Display audit reports
   - Save reusable configurations

3. **CLI Tools**
   - Build Laravel commands to scaffold
   - Automated auditing workflows
   - Integration with build systems

4. **Microservices**
   - Use SES-Queb API from multiple Laravel services
   - Centralized scaffold management
   - Shared security auditing

---

## 📝 Dependencies

- `php: ^8.2` - PHP 8.2 or higher
- `guzzlehttp/guzzle: ^7.0` - HTTP client
- `illuminate/support: ^11.0` - Laravel support

**Dev Dependencies:**
- `phpunit/phpunit: ^10.0` - Testing
- `php-parallel-lint: ^1.3` - Code linting

---

## 🚀 Publishing to Packagist

```bash
# 1. Create GitHub repository
# 2. Push code
# 3. Login to packagist.org
# 4. Submit package
# 5. Once approved:
composer require bhargavkalambhe/ses-queb-sdk
```

---

## ✅ Status

**Complete & Production Ready** ✅

- [x] All API endpoints implemented
- [x] Laravel service provider
- [x] Configuration system
- [x] Error handling
- [x] Documentation
- [x] Examples
- [x] Authentication support

Ready to:
1. Push to GitHub
2. Publish to Packagist
3. Use in other Laravel projects

---

## 📞 Support

- GitHub: https://github.com/bhargavkalambhe/ses-queb-php-sdk
- Issues: GitHub Issues
- Documentation: README.md

---

**Ready to use in your Laravel projects! 🎉**
