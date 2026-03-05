<?php

namespace App\Models;

use App\Enums\AuditType;
use Illuminate\Database\Eloquent\Model;

class AuditReport extends Model
{
    protected $fillable = [
        'report_id',
        'audit_type',
        'project_path',
        'vulnerabilities',
        'license_issues',
        'security_configs',
        'summary',
    ];

    protected $casts = [
        'audit_type' => AuditType::class,
        'vulnerabilities' => 'array',
        'license_issues' => 'array',
        'security_configs' => 'array',
        'summary' => 'array',
    ];
}
