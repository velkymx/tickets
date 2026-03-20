<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token', 'api_token',
    ];

    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket', 'user_id2');
    }

    public function owner()
    {
        return $this->hasMany('App\Models\Ticket', 'user_id');
    }

    public function generateApiToken(): string
    {
        $plain = Str::random(60);

        $this->api_token = hash('sha256', $plain);
        $this->save();

        return $plain;
    }
}
