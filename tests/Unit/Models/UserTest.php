<?php

namespace Tests\Unit\Models;

use App\Models\Mention;
use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $user = new User;
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('avatar', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('timezone', $fillable);
        $this->assertContains('theme', $fillable);
        $this->assertContains('title', $fillable);
        $this->assertContains('bio', $fillable);
    }

    #[Test]
    public function it_hides_sensitive_fields(): void
    {
        $user = new User;
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
        $this->assertContains('api_token', $hidden);
    }

    #[Test]
    public function it_casts_password_as_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(password_verify('plain-password', $user->password));
    }

    #[Test]
    public function it_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
    }

    #[Test]
    public function it_has_many_assigned_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $this->assertTrue($user->tickets->contains($ticket));
        $this->assertInstanceOf(Collection::class, $user->tickets);
    }

    #[Test]
    public function it_has_many_owned_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->owner->contains($ticket));
        $this->assertInstanceOf(Collection::class, $user->owner);
    }

    #[Test]
    public function it_generates_api_token(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->generateApiToken();

        $this->assertIsString($plainToken);
        $this->assertEquals(60, strlen($plainToken));
        $this->assertEquals(hash('sha256', $plainToken), $user->api_token);
    }

    #[Test]
    public function it_generates_unique_api_tokens(): void
    {
        $user = User::factory()->create();
        $token1 = $user->generateApiToken();
        $token2 = $user->generateApiToken();

        $this->assertNotEquals($token1, $token2);
    }

    #[Test]
    public function it_returns_a_storage_url_when_an_avatar_is_present(): void
    {
        $user = User::factory()->make([
            'avatar' => 'avatars/alice.png',
        ]);

        $this->assertStringEndsWith('/avatars/alice.png', $user->avatarUrl());
    }

    #[Test]
    public function it_returns_a_gravatar_url_when_avatar_is_missing(): void
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'avatar' => null,
        ]);

        $expected = 'https://www.gravatar.com/avatar/'.md5('test@example.com').'?s=46&d=mp';

        $this->assertSame($expected, $user->avatarUrl());
    }

    #[Test]
    public function it_has_many_mentions_and_reactions(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $author->id,
            'user_id2' => $author->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        $mention = Mention::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
        ]);
        $reaction = NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);

        $this->assertTrue($user->mentions->contains($mention));
        $this->assertTrue($user->reactions->contains($reaction));
    }
}
