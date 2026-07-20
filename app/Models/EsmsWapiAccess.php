<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsmsWapiAccess extends Model
{
    protected $fillable = [
        'esms_user_id',
        'email',
        'google_id',
        'name',
        'device_number',
        'role',
        'is_active',
        'last_login_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
    ];
}
