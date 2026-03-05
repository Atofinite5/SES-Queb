<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedConfig;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    /**
     * Save a configuration.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:templates,id',
                'name' => 'required|string|max:255|min:3|unique:saved_configs,name',
                'description' => 'string|nullable|max:1000',
                'config' => 'required|array|min:1',
                'config.typescript' => 'boolean',
                'config.testing' => 'string|in:jest,vitest,phpunit',
                'config.linting' => 'boolean',
                'config.state_management' => 'string|nullable',
                'config.routing' => 'boolean|nullable',
            ], [
                'name.unique' => 'A configuration with this name already exists',
                'name.min' => 'Configuration name must be at least 3 characters',
            ]);

            Template::findOrFail($validated['template_id']);

            $config = SavedConfig::create($validated);

            Log::info('Configuration saved', [
                'config_id' => $config->id,
                'template_id' => $config->template_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration saved successfully',
                'data' => [
                    'id' => $config->id,
                    'name' => $config->name,
                    'template_id' => $config->template_id,
                    'created_at' => $config->created_at,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to save configuration', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save configuration',
            ], 500);
        }
    }

    /**
     * List saved configurations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $perPage = min($perPage, 100);
            $templateId = $request->query('template_id');

            $query = SavedConfig::with('template')
                ->orderByDesc('created_at');

            if ($templateId) {
                $query->where('template_id', $templateId);
            }

            $configs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $configs->items(),
                'pagination' => [
                    'total' => $configs->total(),
                    'per_page' => $configs->perPage(),
                    'current_page' => $configs->currentPage(),
                    'last_page' => $configs->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch configurations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch configurations',
            ], 500);
        }
    }

    /**
     * Get a specific configuration.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $config = SavedConfig::with('template')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $config->id,
                    'name' => $config->name,
                    'description' => $config->description,
                    'config' => $config->config,
                    'template' => [
                        'id' => $config->template->id,
                        'name' => $config->template->name,
                        'framework' => $config->template->framework,
                    ],
                    'created_at' => $config->created_at,
                    'updated_at' => $config->updated_at,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch configuration', [
                'config_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch configuration',
            ], 500);
        }
    }

    /**
     * Delete a configuration.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $config = SavedConfig::findOrFail($id);
            $name = $config->name;
            $config->delete();

            Log::info('Configuration deleted', [
                'config_id' => $id,
                'config_name' => $name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete configuration', [
                'config_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete configuration',
            ], 500);
        }
    }
}
