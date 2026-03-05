<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;

class ScaffoldJob extends Model
{
    protected $fillable = [
        'job_id',
        'status',
        'config',
        'output_path',
        'error_message',
        'progress',
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'config' => 'array',
        'progress' => 'integer',
    ];
}
