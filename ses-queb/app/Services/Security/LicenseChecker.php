<?php

namespace App\Services\Security;

use App\Enums\LicenseRisk;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class LicenseChecker
{
    private const ALLOWED_LICENSES = ['MIT', 'Apache-2.0', 'BSD-3-Clause', 'ISC', 'GPL-3.0'];
    private const RESTRICTED_LICENSES = ['AGPL-3.0', 'SSPL'];

    /**
     * Check licenses in project dependencies.
     */
    public function check(string $projectPath): array
    {
        if (!file_exists("$projectPath/package.json")) {
            return [];
        }

        try {
            $licenses = $this->extractLicenses($projectPath);
            $issues = [];

            foreach ($licenses as $package => $license) {
                $risk = $this->assessRisk($license);

                if ($risk === LicenseRisk::HIGH) {
                    $issues[] = [
                        'type' => 'restricted_license',
                        'severity' => 'high',
                        'package' => $package,
                        'license' => $license,
                        'message' => "Package '$package' uses restricted license: $license",
                    ];
                }
            }

            $compatibility = $this->checkCompatibility(array_values($licenses));
            if (!empty($compatibility['conflicts'])) {
                $issues[] = [
                    'type' => 'license_conflict',
                    'severity' => 'medium',
                    'message' => 'License compatibility issues detected',
                    'conflicts' => $compatibility['conflicts'],
                ];
            }

            return $issues;

        } catch (\Exception $e) {
            Log::error('License check failed', [
                'path' => $projectPath,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Assess license risk level.
     */
    protected function assessRisk(string $license): LicenseRisk
    {
        if (in_array($license, self::RESTRICTED_LICENSES)) {
            return LicenseRisk::HIGH;
        }

        if (in_array($license, self::ALLOWED_LICENSES)) {
            return LicenseRisk::LOW;
        }

        return LicenseRisk::MEDIUM;
    }

    /**
     * Check license compatibility.
     */
    protected function checkCompatibility(array $licenses): array
    {
        $conflicts = [];

        $hasGPL = in_array('GPL-3.0', $licenses);
        $hasProprietary = in_array('proprietary', $licenses);

        if ($hasGPL && $hasProprietary) {
            $conflicts[] = 'GPL-3.0 and Proprietary licenses are incompatible';
        }

        return ['conflicts' => $conflicts];
    }

    /**
     * Extract licenses from dependencies.
     */
    private function extractLicenses(string $projectPath): array
    {
        try {
            $result = Process::path($projectPath)->run('npm ls --depth=0 --json');
            $output = json_decode($result->output(), true);

            $licenses = [];
            if (isset($output['dependencies'])) {
                foreach ($output['dependencies'] as $package => $info) {
                    $licenses[$package] = $this->getLicenseFromPackage($projectPath, $package);
                }
            }

            return $licenses;

        } catch (\Exception $e) {
            Log::error('Could not extract licenses', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get license from package.json.
     */
    private function getLicenseFromPackage(string $projectPath, string $package): string
    {
        try {
            $packagePath = "$projectPath/node_modules/$package/package.json";
            if (file_exists($packagePath)) {
                $json = json_decode(file_get_contents($packagePath), true);
                return $json['license'] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            Log::debug('Could not read package license', ['package' => $package]);
        }

        return 'Unknown';
    }
}
