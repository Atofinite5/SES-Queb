<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedConfig extends Model
{
    protected $fillable = [
        'name',
        'description',
        'config',
        'template_id',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
