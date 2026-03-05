<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name',
        'description',
        'framework',
        'features',
        'default_config',
    ];

    protected $casts = [
        'features' => 'array',
        'default_config' => 'array',
    ];

    public function savedConfigs()
    {
        return $this->hasMany(SavedConfig::class);
    }
}
