<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    /**
     * List available templates.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $framework = $request->query('framework');
            $perPage = $request->query('per_page', 15);
            $perPage = min($perPage, 100);

            $query = Template::with('savedConfigs')
                ->orderBy('name');

            if ($framework) {
                $query->where('framework', $framework);
            }

            $templates = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $templates->items(),
                'pagination' => [
                    'total' => $templates->total(),
                    'per_page' => $templates->perPage(),
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch templates', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch templates',
            ], 500);
        }
    }

    /**
     * Get template details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $template = Template::with('savedConfigs')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'framework' => $template->framework,
                    'features' => $template->features ?? [],
                    'default_config' => $template->default_config ?? [],
                    'saved_configs_count' => $template->savedConfigs()->count(),
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch template',
            ], 500);
        }
    }
}
