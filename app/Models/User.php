<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            TicketView::where('user_id', $user->id)->delete();
            TicketUserWatcher::where('user_id', $user->id)->delete();
        });
    }

    protected $fillable = [
        'name', 'email', 'avatar', 'password', 'phone', 'timezone', 'theme', 'title', 'bio',
    ];

    public function avatarUrl(int $size = 46): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return "https://www.gravatar.com/avatar/{$this->gravatar_hash}?s={$size}&d=mp";
    }

    protected function gravatarHash(): Attribute
    {
        return Attribute::get(fn () => md5(strtolower(trim($this->email ?? ''))));
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
            'active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->admin;
    }

    public function isKbAdmin(): bool
    {
        return $this->isAdmin() || $this->kb_role === 'admin';
    }

    public function isKbAuthor(): bool
    {
        return $this->isKbAdmin() || $this->kb_role === 'author';
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id2');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
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
