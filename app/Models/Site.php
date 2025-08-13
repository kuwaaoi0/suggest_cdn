<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'name','site_key','api_key','jwt_secret','jwt_issuer',
        'rate_limit_per_min','allowed_origins','is_active',
    ];

    protected $casts = [
        'allowed_origins' => 'array',
        'is_active' => 'bool',
    ];

    /** このサイトに所属するユーザー（多対多） */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)
            ->withTimestamps()
            ->withPivot('role');
    }
}
