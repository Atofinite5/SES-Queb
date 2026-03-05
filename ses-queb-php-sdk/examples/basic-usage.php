<?php

/**
 * SES-Queb PHP SDK - Basic Usage Examples
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BhargavKalambhe\SESQuebSDK\SESQuebClient;
use BhargavKalambhe\SESQuebSDK\SESQuebException;

// ==========================================
// EXAMPLE 1: List Templates
// ==========================================
function listTemplates()
{
    echo "📚 Available Templates:\n\n";

    $client = new SESQuebClient();

    try {
        $templates = $client->getTemplates();

        foreach ($templates as $template) {
            echo "ID: {$template['id']}\n";
            echo "Name: {$template['name']}\n";
            echo "Framework: {$template['framework']}\n";
            echo "---\n";
        }
    } catch (SESQuebException $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

// ==========================================
// EXAMPLE 2: Scaffold a Project
// ==========================================
function scaffoldProject()
{
    echo "📦 Creating React project...\n\n";

    $client = new SESQuebClient();

    try {
        // Create scaffold job
        $job = $client->scaffold(1, 'my-react-app', [
            'typescript' => true,
            'linting' => true,
        ]);

        echo "✅ Job created: {$job['id']}\n";
        echo "📍 Status: {$job['status']}\n\n";

        // Wait for completion
        echo "⏳ Waiting for project generation...\n";
        $completed = $client->waitForScaffold($job['id']);

        echo "✅ Project ready!\n\n";

        // Get download URL
        $downloadUrl = $client->downloadScaffold($job['id']);
        echo "📥 Download URL: {$downloadUrl}\n\n";
    } catch (SESQuebException $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

// ==========================================
// EXAMPLE 3: Run Security Audit
// ==========================================
function auditProject()
{
    echo "🔒 Running security audit...\n\n";

    $client = new SESQuebClient();

    try {
        $report = $client->audit('/path/to/project', 'full');

        echo "📋 Audit Report: {$report['id']}\n\n";

        // Show vulnerabilities
        echo "🚨 Vulnerabilities: " . count($report['vulnerabilities']) . "\n";
        foreach ($report['vulnerabilities'] as $vuln) {
            echo "  [" . strtoupper($vuln['severity']) . "] {$vuln['package']}\n";
            echo "    → {$vuln['description']}\n\n";
        }

        // Show outdated packages
        echo "⬆️ Outdated Packages: " . count($report['outdated_packages']) . "\n";
        foreach ($report['outdated_packages'] as $pkg) {
            echo "  {$pkg['package']}: {$pkg['current_version']} → {$pkg['latest_version']}\n";
        }

        // Show licenses
        echo "\n📜 Licenses: " . count($report['licenses']) . "\n";
        foreach ($report['licenses'] as $lic) {
            $riskLevel = $lic['risk_level'];
            if ($riskLevel === 'high' || $riskLevel === 'medium') {
                echo "  ⚠️  {$lic['package']}: {$lic['license']} ({$riskLevel} risk)\n";
            }
        }
    } catch (SESQuebException $e) {
        echo "❌ Error: {$e->getMessage()}\n";

        if ($e->hasErrors()) {
            echo "\nValidation errors:\n";
            print_r($e->getErrors());
        }
    }
}

// ==========================================
// EXAMPLE 4: Save Configuration
// ==========================================
function saveConfiguration()
{
    echo "💾 Saving configuration...\n\n";

    $client = new SESQuebClient();

    try {
        $config = $client->saveConfig(
            1,
            'React + TypeScript + Lint',
            [
                'typescript' => true,
                'linting' => true,
                'prettier' => true,
            ]
        );

        echo "✅ Config saved: {$config['id']}\n";
        echo "Name: {$config['name']}\n\n";

        // Use saved config
        echo "📦 Creating project from saved config...\n";
        $job = $client->scaffold($config['template_id'], 'my-app', $config['config']);

        echo "✅ Job created: {$job['id']}\n";
    } catch (SESQuebException $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

// ==========================================
// EXAMPLE 5: Check API Health
// ==========================================
function checkHealth()
{
    echo "🏥 Checking API health...\n\n";

    $client = new SESQuebClient();
    $health = $client->health();

    echo "Status: {$health['status']}\n";

    if ($health['status'] === 'ok') {
        echo "✅ API is healthy\n";
    } else {
        echo "❌ API is down\n";
    }
}

// ==========================================
// EXAMPLE 6: Authentication
// ==========================================
function withAuthentication()
{
    echo "🔐 Using authentication...\n\n";

    $client = new SESQuebClient();

    // Set auth token
    $client->setAuthToken('your-api-token-here');

    try {
        $templates = $client->getTemplates();
        echo "✅ Authenticated request successful\n";
        echo "Templates: " . count($templates) . "\n";
    } catch (SESQuebException $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }

    // Remove token
    $client->removeAuthToken();
}

// ==========================================
// RUN EXAMPLES
// ==========================================

echo str_repeat('=', 50) . "\n";
echo "SES-Queb PHP SDK Examples\n";
echo str_repeat('=', 50) . "\n\n";

// Uncomment to run examples:

// listTemplates();
// echo "\n" . str_repeat('-', 50) . "\n\n";

// scaffoldProject();
// echo "\n" . str_repeat('-', 50) . "\n\n";

// auditProject();
// echo "\n" . str_repeat('-', 50) . "\n\n";

// saveConfiguration();
// echo "\n" . str_repeat('-', 50) . "\n\n";

// checkHealth();
// echo "\n" . str_repeat('-', 50) . "\n\n";

// withAuthentication();
