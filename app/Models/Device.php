<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'body', 'webhook', 'status', 'message_sent', 'api_token'];
	protected $casts = [
		'llm_models' => 'array',
	];

    protected static function booted()
    {
        static::creating(function (Device $device) {
            if (empty($device->api_token)) {
                $device->api_token = static::generateApiToken();
            }
        });
    }

    public static function generateApiToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::query()->where('api_token', $token)->exists());

        return $token;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function autoreplies()
    {
        return $this->hasMany(Autoreply::class, 'device', 'body');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }


    
}
