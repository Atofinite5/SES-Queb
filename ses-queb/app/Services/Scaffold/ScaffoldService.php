<?php

namespace App\Services\Scaffold;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;
use Exception;

class ScaffoldService
{
    private const SCAFFOLD_BASE_PATH = 'storage/scaffolds';

    /**
     * Create a new scaffold job.
     */
    public function createJob(array $validated): array
    {
        if (!$this->validateConfig($validated['config'])) {
            throw new Exception('Invalid scaffold configuration');
        }

        return [
            'job_id' => (string) Str::uuid(),
            'status' => 'pending',
            'config' => $validated['config'],
            'progress' => 0,
        ];
    }

    /**
     * Generate project scaffold based on configuration.
     */
    public function generate(array $config): string
    {
        $projectPath = $this->getProjectPath($config);

        try {
            $this->createStructure($projectPath, $config);
            $this->createConfigFiles($projectPath, $config);
            $this->installDependencies($projectPath, $config);

            return $projectPath;
        } catch (Exception $e) {
            $this->cleanup($projectPath);
            throw new Exception("Scaffold generation failed: " . $e->getMessage());
        }
    }

    /**
     * Validate scaffold configuration.
     */
    public function validateConfig(array $config): bool
    {
        $required = ['name', 'framework', 'typescript'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }

        $allowedFrameworks = ['node', 'react', 'vue'];
        if (!in_array($config['framework'], $allowedFrameworks)) {
            return false;
        }

        return true;
    }

    /**
     * Create project structure.
     */
    protected function createStructure(string $path, array $config): void
    {
        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new Exception("Failed to create project directory: $path");
        }

        $framework = $config['framework'];
        $directories = $this->getDirectoryStructure($framework);

        foreach ($directories as $dir) {
            $fullPath = "$path/$dir";
            if (!@mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                Log::warning('Could not create directory', ['path' => $fullPath]);
            }
        }

        Log::info('Project structure created', [
            'path' => $path,
            'framework' => $framework,
        ]);
    }

    /**
     * Create configuration files.
     */
    protected function createConfigFiles(string $path, array $config): void
    {
        $framework = $config['framework'];

        if ($config['typescript'] ?? false) {
            $this->createTsConfig($path);
        }

        if ($config['linting'] ?? false) {
            $this->createEsLintConfig($path);
            $this->createPrettierConfig($path);
        }

        $this->createPackageJson($path, $config);
        $this->createGitIgnore($path);
        $this->createReadme($path, $config);

        Log::info('Configuration files created', ['path' => $path]);
    }

    /**
     * Install dependencies.
     */
    protected function installDependencies(string $path, array $config): void
    {
        try {
            $packageManager = $this->detectPackageManager($path);

            Log::info('Installing dependencies', [
                'path' => $path,
                'package_manager' => $packageManager,
            ]);

            $commands = [
                'npm' => 'npm install',
                'yarn' => 'yarn install',
                'pnpm' => 'pnpm install',
            ];

            $command = $commands[$packageManager] ?? 'npm install';
            Process::path($path)->run($command);

            Log::info('Dependencies installed', ['path' => $path]);
        } catch (Exception $e) {
            Log::error('Failed to install dependencies', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create tsconfig.json.
     */
    private function createTsConfig(string $path): void
    {
        $tsconfig = [
            'compilerOptions' => [
                'target' => 'ES2020',
                'useDefineForClassFields' => true,
                'lib' => ['ES2020', 'DOM', 'DOM.Iterable'],
                'module' => 'ESNext',
                'skipLibCheck' => true,
                'strict' => true,
                'resolveJsonModule' => true,
            ],
        ];

        file_put_contents(
            "$path/tsconfig.json",
            json_encode($tsconfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Create ESLint config.
     */
    private function createEsLintConfig(string $path): void
    {
        $eslint = [
            'env' => ['browser' => true, 'es2021' => true],
            'extends' => ['eslint:recommended'],
            'parserOptions' => ['ecmaVersion' => 'latest'],
            'rules' => [
                'no-console' => 'warn',
                'no-debugger' => 'warn',
            ],
        ];

        file_put_contents(
            "$path/.eslintrc.json",
            json_encode($eslint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Create Prettier config.
     */
    private function createPrettierConfig(string $path): void
    {
        $prettier = [
            'semi' => true,
            'singleQuote' => true,
            'trailingComma' => 'es5',
            'printWidth' => 80,
            'tabWidth' => 2,
        ];

        file_put_contents(
            "$path/.prettierrc.json",
            json_encode($prettier, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Create package.json.
     */
    private function createPackageJson(string $path, array $config): void
    {
        $packageJson = [
            'name' => str_replace(' ', '-', strtolower($config['name'] ?? 'project')),
            'version' => '0.0.1',
            'description' => 'Scaffolded project',
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
                'lint' => 'eslint src',
                'format' => 'prettier --write src',
            ],
            'devDependencies' => $this->getDevDependencies($config),
            'dependencies' => $this->getDependencies($config),
        ];

        file_put_contents(
            "$path/package.json",
            json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Create .gitignore.
     */
    private function createGitIgnore(string $path): void
    {
        $gitignore = <<<'EOF'
node_modules/
dist/
.env
.env.local
.env.*.local
.DS_Store
*.log
.idea/
.vscode/
*.swp
*.swo
EOF;

        file_put_contents("$path/.gitignore", $gitignore);
    }

    /**
     * Create README.md.
     */
    private function createReadme(string $path, array $config): void
    {
        $framework = ucfirst($config['framework']);
        $readme = <<<EOF
# {$config['name']}

This project was scaffolded using SES-Queb.

## Technology Stack

- Framework: $framework
- TypeScript: {$this->boolToString($config['typescript'] ?? false)}
- Linting: {$this->boolToString($config['linting'] ?? false)}

## Getting Started

### Install Dependencies

npm install

### Development

npm run dev

### Build

npm run build

## License

MIT
EOF;

        file_put_contents("$path/README.md", $readme);
    }

    /**
     * Get directory structure for framework.
     */
    private function getDirectoryStructure(string $framework): array
    {
        $structures = [
            'react' => [
                'src',
                'src/components',
                'src/pages',
                'src/hooks',
                'public',
                'tests',
            ],
            'vue' => [
                'src',
                'src/components',
                'src/pages',
                'src/composables',
                'public',
                'tests',
            ],
            'node' => [
                'src',
                'src/routes',
                'src/middleware',
                'src/controllers',
                'src/services',
                'tests',
                'config',
            ],
        ];

        return $structures[$framework] ?? [];
    }

    /**
     * Get dev dependencies for framework.
     */
    private function getDevDependencies(array $config): array
    {
        $base = [
            'vite' => '^4.0.0',
            'eslint' => '^8.0.0',
            'prettier' => '^3.0.0',
        ];

        if ($config['typescript'] ?? false) {
            $base['typescript'] = '^5.0.0';
            $base['@types/node'] = '^20.0.0';
        }

        if ($config['testing'] ?? null === 'vitest') {
            $base['vitest'] = '^1.0.0';
        } elseif ($config['testing'] ?? null === 'jest') {
            $base['jest'] = '^29.0.0';
        }

        return $base;
    }

    /**
     * Get dependencies for framework.
     */
    private function getDependencies(array $config): array
    {
        $framework = $config['framework'];

        $dependencies = match ($framework) {
            'react' => [
                'react' => '^18.0.0',
                'react-dom' => '^18.0.0',
            ],
            'vue' => [
                'vue' => '^3.0.0',
            ],
            'node' => [
                'express' => '^4.18.0',
            ],
            default => [],
        };

        return $dependencies;
    }

    /**
     * Detect package manager from package-lock.json or yarn.lock.
     */
    private function detectPackageManager(string $path): string
    {
        if (file_exists("$path/yarn.lock")) {
            return 'yarn';
        }
        if (file_exists("$path/pnpm-lock.yaml")) {
            return 'pnpm';
        }
        return 'npm';
    }

    /**
     * Get project path.
     */
    private function getProjectPath(array $config): string
    {
        $basePath = storage_path(self::SCAFFOLD_BASE_PATH);
        return $basePath . '/' . str_replace(' ', '-', strtolower($config['name']));
    }

    /**
     * Cleanup project directory on failure.
     */
    private function cleanup(string $path): void
    {
        if (is_dir($path)) {
            exec("rm -rf '$path'");
        }
    }

    /**
     * Convert boolean to string.
     */
    private function boolToString(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
