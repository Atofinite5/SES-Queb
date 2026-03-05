<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'React + Vite',
                'description' => 'Modern React application with Vite',
                'framework' => 'react',
                'features' => ['typescript', 'eslint', 'prettier', 'vitest'],
                'default_config' => [
                    'typescript' => true,
                    'testing' => 'vitest',
                    'linting' => true,
                ],
            ],
            [
                'name' => 'Vue 3 + Vite',
                'description' => 'Vue 3 application with Composition API',
                'framework' => 'vue',
                'features' => ['typescript', 'pinia', 'vue-router', 'vitest'],
                'default_config' => [
                    'typescript' => true,
                    'state_management' => 'pinia',
                    'routing' => true,
                ],
            ],
            [
                'name' => 'Node.js API',
                'description' => 'Express.js REST API with security best practices',
                'framework' => 'node',
                'features' => ['express', 'typescript', 'jest', 'helmet'],
                'default_config' => [
                    'typescript' => true,
                    'testing' => 'jest',
                    'security' => true,
                ],
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}
