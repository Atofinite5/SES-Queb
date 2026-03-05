<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Encrypted;

class GitHubConnection extends Model
{
    protected $fillable = [
        'user_id',
        'github_username',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => Encrypted::class,
        'refresh_token' => Encrypted::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Check if token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Get decrypted access token.
     */
    public function getDecryptedAccessToken(): string
    {
        return (string) $this->access_token;
    }
}
