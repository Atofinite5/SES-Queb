<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditReport;
use App\Services\Security\VulnerabilityScanner;
use App\Services\Security\LicenseChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditController extends Controller
{
    public function __construct(
        private VulnerabilityScanner $vulnerabilityScanner,
        private LicenseChecker $licenseChecker
    ) {}

    /**
     * Run security audit on a project.
     */
    public function audit(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_path' => 'required|string|filled',
                'audit_type' => 'required|string|in:vulnerability,license,security_config,full',
                'include_dev_dependencies' => 'boolean|nullable',
            ], [
                'project_path.required' => 'Project path is required',
                'audit_type.required' => 'Audit type is required',
                'audit_type.in' => 'Invalid audit type. Choose: vulnerability, license, security_config, full',
            ]);

            if (!is_dir($validated['project_path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project path does not exist or is not accessible',
                ], 422);
            }

            $reportId = (string) Str::uuid();
            $vulnerabilities = [];
            $licenseIssues = [];
            $securityConfigs = [];

            if (in_array($validated['audit_type'], ['vulnerability', 'full'])) {
                $vulnerabilities = $this->vulnerabilityScanner->scan(
                    $validated['project_path'],
                    $validated['include_dev_dependencies'] ?? false
                );
            }

            if (in_array($validated['audit_type'], ['license', 'full'])) {
                $licenseIssues = $this->licenseChecker->check(
                    $validated['project_path']
                );
            }

            if (in_array($validated['audit_type'], ['security_config', 'full'])) {
                $securityConfigs = $this->analyzeSecurityConfig($validated['project_path']);
            }

            $report = AuditReport::create([
                'report_id' => $reportId,
                'audit_type' => $validated['audit_type'],
                'project_path' => $validated['project_path'],
                'vulnerabilities' => $vulnerabilities,
                'license_issues' => $licenseIssues,
                'security_configs' => $securityConfigs,
                'summary' => [
                    'vulnerability_count' => count($vulnerabilities),
                    'license_issues_count' => count($licenseIssues),
                    'security_issues_count' => count($securityConfigs),
                    'critical_count' => $this->countBySeverity($vulnerabilities, 'critical'),
                    'high_count' => $this->countBySeverity($vulnerabilities, 'high'),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::info('Audit report created', [
                'report_id' => $reportId,
                'audit_type' => $validated['audit_type'],
                'path' => $validated['project_path'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit completed successfully',
                'data' => [
                    'report_id' => $report->report_id,
                    'audit_type' => $report->audit_type,
                    'summary' => $report->summary,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Audit failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Audit failed',
            ], 500);
        }
    }

    /**
     * Get audit report.
     */
    public function report(string $reportId): JsonResponse
    {
        try {
            $report = AuditReport::where('report_id', $reportId)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'report_id' => $report->report_id,
                    'audit_type' => $report->audit_type,
                    'project_path' => $report->project_path,
                    'vulnerabilities' => $report->vulnerabilities ?? [],
                    'license_issues' => $report->license_issues ?? [],
                    'security_configs' => $report->security_configs ?? [],
                    'summary' => $report->summary,
                    'created_at' => $report->created_at,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Audit report not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch audit report', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch report',
            ], 500);
        }
    }

    /**
     * List all audit reports.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $perPage = min($perPage, 100); // Max 100 per page

            $reports = AuditReport::orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reports->items(),
                'pagination' => [
                    'total' => $reports->total(),
                    'per_page' => $reports->perPage(),
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch audit reports', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports',
            ], 500);
        }
    }

    /**
     * Analyze security configuration.
     */
    private function analyzeSecurityConfig(string $projectPath): array
    {
        $issues = [];

        $configFiles = ['tsconfig.json', 'package.json', '.env.example'];
        foreach ($configFiles as $file) {
            if (!file_exists($projectPath . '/' . $file)) {
                $issues[] = [
                    'type' => 'missing_config',
                    'file' => $file,
                    'severity' => 'medium',
                    'message' => "Missing configuration file: $file",
                ];
            }
        }

        return $issues;
    }

    /**
     * Count vulnerabilities by severity.
     */
    private function countBySeverity(array $vulnerabilities, string $severity): int
    {
        return count(array_filter($vulnerabilities, fn ($v) => ($v['severity'] ?? null) === $severity));
    }
}
