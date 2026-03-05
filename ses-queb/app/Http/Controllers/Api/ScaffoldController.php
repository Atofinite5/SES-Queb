<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessScaffoldJob;
use App\Models\ScaffoldJob;
use App\Models\Template;
use App\Services\Scaffold\ScaffoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScaffoldController extends Controller
{
    public function __construct(
        private ScaffoldService $scaffoldService
    ) {}

    /**
     * Create a new scaffold job.
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:templates,id',
                'name' => 'required|string|max:255|min:3',
                'config' => 'required|array|min:1',
                'config.typescript' => 'boolean',
                'config.testing' => 'string|in:jest,vitest,phpunit',
                'config.linting' => 'boolean',
                'github_push' => 'boolean|nullable',
                'github_repo_name' => 'string|max:255|nullable|required_if:github_push,true',
            ], [
                'template_id.required' => 'Template is required',
                'template_id.exists' => 'Selected template does not exist',
                'name.required' => 'Project name is required',
                'name.min' => 'Project name must be at least 3 characters',
                'config.required' => 'Configuration is required',
            ]);

            $template = Template::findOrFail($validated['template_id']);

            $job = ScaffoldJob::create([
                'job_id' => (string) Str::uuid(),
                'status' => 'pending',
                'config' => array_merge(
                    $template->default_config ?? [],
                    $validated['config']
                ),
                'progress' => 0,
            ]);

            ProcessScaffoldJob::dispatch($job->job_id, $job->config);

            Log::info('Scaffold job created', [
                'job_id' => $job->job_id,
                'template_id' => $template->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scaffold job created successfully',
                'data' => [
                    'job_id' => $job->job_id,
                    'status' => $job->status,
                    'progress' => $job->progress,
                    'created_at' => $job->created_at,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Scaffold job creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create scaffold job',
            ], 500);
        }
    }

    /**
     * Get scaffold job status.
     */
    public function status(string $jobId): JsonResponse
    {
        try {
            $job = ScaffoldJob::where('job_id', $jobId)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $job->job_id,
                    'status' => $job->status,
                    'progress' => $job->progress,
                    'created_at' => $job->created_at,
                    'completed_at' => $job->updated_at,
                    'error' => $job->error_message,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch status',
            ], 500);
        }
    }

    /**
     * Download scaffolded project.
     */
    public function download(string $jobId)
    {
        try {
            $job = ScaffoldJob::where('job_id', $jobId)->firstOrFail();

            if ($job->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Project is not ready for download',
                    'current_status' => $job->status,
                ], 422);
            }

            if (!$job->output_path || !file_exists($job->output_path)) {
                Log::error('Project file not found', [
                    'job_id' => $jobId,
                    'output_path' => $job->output_path,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Project file not found',
                ], 404);
            }

            return response()->download($job->output_path, "project-{$jobId}.zip");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Download failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Download failed',
            ], 500);
        }
    }
}
