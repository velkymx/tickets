<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'phone', 'timezone', 'theme', 'title', 'bio',
    ];

    public function avatarUrl(int $size = 46): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(NoteReaction::class);
    }

    protected $hidden = [
        'password', 'remember_token', 'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->admin;
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id2');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    /** @deprecated Use assignedTickets() */
    public function tickets(): HasMany
    {
        return $this->assignedTickets();
    }

    /** @deprecated Use createdTickets() */
    public function owner(): HasMany
    {
        return $this->createdTickets();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'user_id');
    }

    public function generateApiToken(): string
    {
        $plain = Str::random(60);

        $this->api_token = hash('sha256', $plain);
        $this->save();

        return $plain;
    }
}
