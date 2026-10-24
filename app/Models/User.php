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
            'notification_preferences' => 'array',
        ];
    }

    public const NOTIFICATION_PREF_DEFAULTS = [
        'email_enabled' => true,
        'email_mode' => 'digest',
        'mentions' => true,
        'replies' => true,
        'watching' => true,
    ];

    /**
     * Preferences with defaults merged on read, so existing users get
     * today's behavior without a backfill.
     *
     * @return array{email_enabled: bool, email_mode: string, mentions: bool, replies: bool, watching: bool}
     */
    public function notificationPrefs(): array
    {
        return array_merge(self::NOTIFICATION_PREF_DEFAULTS, $this->notification_preferences ?? []);
    }

    public function setNotificationPrefs(array $prefs): void
    {
        $this->forceFill([
            'notification_preferences' => array_merge($this->notificationPrefs(), $prefs),
        ])->save();
    }

    /**
     * Whether the category reaches the user at all. Database delivery is
     * never gated here; mail gating lives in each via().
     */
    public function wantsNotification(string $type): bool
    {
        return match ($type) {
            'mention', 'decision_request' => (bool) $this->notificationPrefs()['mentions'],
            'reply' => (bool) $this->notificationPrefs()['replies'],
            'watching' => (bool) $this->notificationPrefs()['watching'],
            default => $this->unknownNotificationType($type),
        };
    }

    /**
     * Whether mail may go out for a category: master switch plus category.
     */
    public function mailsFor(string $category): bool
    {
        $prefs = $this->notificationPrefs();

        return (bool) $prefs['email_enabled'] && (bool) ($prefs[$category] ?? true);
    }

    /**
     * Unknown types fail loud outside production so a new notification
     * without a mapped preference cannot silently bypass every opt-out.
     * Production still delivers rather than dropping user-visible mail.
     */
    private function unknownNotificationType(string $type): bool
    {
        if (! app()->isProduction()) {
            throw new \InvalidArgumentException("Unknown notification type [{$type}]: map it to a preference in wantsNotification().");
        }

        return true;
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

    public function savedViews(): HasMany
    {
        return $this->hasMany(SavedView::class)->orderBy('name');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function generateApiToken(): string
    {
        $plain = Str::random(60);

        $this->api_token = hash('sha256', $plain);
        $this->save();

        return $plain;
    }
}
