<?php

namespace Tests\Feature\Controllers;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->post('/notes/hide/1');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_allows_note_author_to_hide(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'hide' => false]);

        $response = $this->actingAs($user)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_allows_ticket_creator_to_hide(): void
    {
        $creator = User::factory()->create();
        $noteAuthor = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $noteAuthor->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $noteAuthor->id, 'hide' => false]);

        $response = $this->actingAs($creator)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_allows_ticket_assignee_to_hide(): void
    {
        $assignee = User::factory()->create();
        $creator = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $assignee->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $creator->id, 'hide' => false]);

        $response = $this->actingAs($assignee)->post("/notes/hide/{$note->id}");

        $response->assertJson(['message' => 'Note Removed!']);
        $this->assertTrue($note->fresh()->hide);
    }

    #[Test]
    public function it_denies_unrelated_user(): void
    {
        $creator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $creator->id, 'user_id2' => $creator->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $creator->id, 'hide' => false]);

        $response = $this->actingAs($unrelatedUser)->post("/notes/hide/{$note->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_note(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notes/hide/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_requires_authentication_to_promote_a_note(): void
    {
        $response = $this->post('/notes/1/promote', [
            'type' => 'decision',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_promotes_a_message_note_to_decision_when_substantive(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'Use Redis-backed advisory locks for all write serialization.',
        ]);

        $response = $this->actingAs($user)->post("/notes/{$note->id}/promote", [
            'type' => 'decision',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals('decision', $note->fresh()->notetype);
    }

    #[Test]
    public function it_rejects_short_decision_promotions(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'ok lets do it',
        ]);

        $response = $this->actingAs($user)->from("/tickets/{$ticket->id}")->post("/notes/{$note->id}/promote", [
            'type' => 'decision',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $response->assertSessionHasErrors(['type']);
        $this->assertEquals('message', $note->fresh()->notetype);
    }

    #[Test]
    public function it_requires_an_assignee_to_promote_a_note_to_action(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'Verify the staging deploy before release.',
        ]);

        $response = $this->actingAs($user)->from("/tickets/{$ticket->id}")->post("/notes/{$note->id}/promote", [
            'type' => 'action',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $response->assertSessionHasErrors(['type']);
        $this->assertEquals('message', $note->fresh()->notetype);
    }

    #[Test]
    public function it_can_promote_a_note_to_action_with_a_prompted_assignee(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'sarah']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'Verify the staging deploy before release.',
        ]);

        $response = $this->actingAs($user)->post("/notes/{$note->id}/promote", [
            'type' => 'action',
            'assignee' => 'sarah',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals('action', $note->fresh()->notetype);
        $this->assertStringContainsString('@[sarah]', $note->fresh()->body);
    }

    // --- Reply Tests ---

    #[Test]
    public function it_requires_authentication_to_reply(): void
    {
        $response = $this->postJson('/notes/reply', [
            'ticket_id' => 1,
            'parent_id' => 1,
            'body' => 'test reply',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_reply_to_a_top_level_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body' => 'This is a reply',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_id', $parent->id);
        $response->assertJsonPath('body_markdown', 'This is a reply');
        $this->assertDatabaseHas('notes', [
            'parent_id' => $parent->id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_rejects_reply_to_a_reply(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $parent = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);
        $reply = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $reply->id,
            'body' => 'Nested reply attempt',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    #[Test]
    public function it_rejects_reply_when_parent_belongs_to_different_ticket(): void
    {
        $user = User::factory()->create();
        $ticket1 = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $ticket2 = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $parent = Note::factory()->create(['ticket_id' => $ticket1->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket2->id,
            'parent_id' => $parent->id,
            'body' => 'Cross-ticket reply',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    // --- Edit Tests ---

    #[Test]
    public function it_allows_author_to_edit_own_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => '<p>Original</p>',
            'body_markdown' => 'Original',
            'notetype' => 'message',
        ]);

        $response = $this->actingAs($user)->putJson("/notes/{$note->id}", [
            'body' => 'Updated content',
        ]);

        $response->assertOk();
        $response->assertJsonPath('body_markdown', 'Updated content');
        $this->assertNotNull($note->fresh()->edited_at);
    }

    #[Test]
    public function it_denies_non_author_from_editing(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'notetype' => 'message',
        ]);

        $response = $this->actingAs($other)->putJson("/notes/{$note->id}", [
            'body' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function it_blocks_editing_decision_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'decision',
            'body' => 'We decided to use Redis locks for all write serialization.',
        ]);

        $response = $this->actingAs($user)->putJson("/notes/{$note->id}", [
            'body' => 'Changed my mind',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Decisions cannot be edited. Use /decision to create a new superseding decision.');
    }

    // --- Pin/Resolve Tests ---

    #[Test]
    public function it_toggles_pin_on_a_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'pinned' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/pin");

        $response->assertOk();
        $this->assertTrue($note->fresh()->pinned);

        // Toggle off
        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/pin");
        $this->assertFalse($note->fresh()->pinned);
    }

    #[Test]
    public function it_resolves_a_thread_with_message(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);
        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'resolved' => false,
        ]);

        $response = $this->actingAs($author)->postJson("/notes/{$parent->id}/resolve", [
            'resolution_message' => 'Fixed in commit abc123',
        ]);

        $response->assertOk();
        $this->assertTrue($parent->fresh()->resolved);
        $this->assertEquals($author->id, $parent->fresh()->resolved_by);
        $this->assertEquals('Fixed in commit abc123', $parent->fresh()->resolution_message);
    }

    #[Test]
    public function it_rejects_resolve_without_message(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'resolved' => false,
        ]);

        $response = $this->actingAs($author)->postJson("/notes/{$note->id}/resolve", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['resolution_message']);
    }

    #[Test]
    public function it_denies_resolve_by_non_author_non_assignee(): void
    {
        $author = User::factory()->create();
        $assignee = User::factory()->create();
        $outsider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $assignee->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'resolved' => false,
        ]);

        $response = $this->actingAs($outsider)->postJson("/notes/{$note->id}/resolve", [
            'resolution_message' => 'Trying to resolve',
        ]);

        $response->assertForbidden();
    }

    // --- Attachment Tests ---

    #[Test]
    public function it_uploads_an_attachment_to_an_existing_note(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        $file = UploadedFile::fake()->image('screenshot.png', 200, 200);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('filename', 'screenshot.png');
        $response->assertJsonPath('isImage', true);
        $this->assertDatabaseHas('note_attachments', [
            'note_id' => $note->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    #[Test]
    public function it_rejects_oversized_attachments(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        $file = UploadedFile::fake()->create('huge.pdf', 11000); // 11MB

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function it_rejects_disallowed_file_types(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        $file = UploadedFile::fake()->create('malware.exe', 100);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // --- Presence Tests ---

    #[Test]
    public function it_records_presence_heartbeat(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = $this->actingAs($user)->postJson("/tickets/{$ticket->id}/presence");

        $response->assertOk();
        $response->assertJsonStructure(['viewers', 'count']);
        $response->assertJsonPath('count', 1);
    }

    #[Test]
    public function it_returns_current_viewers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user1->id, 'user_id2' => $user2->id]);

        // Both users send heartbeat
        $this->actingAs($user1)->postJson("/tickets/{$ticket->id}/presence");
        $this->actingAs($user2)->postJson("/tickets/{$ticket->id}/presence");

        $response = $this->actingAs($user1)->getJson("/tickets/{$ticket->id}/presence");

        $response->assertOk();
        $response->assertJsonPath('count', 2);
    }

    // --- Reaction Tests ---

    #[Test]
    public function it_requires_authentication_to_toggle_a_reaction(): void
    {
        $response = $this->post('/notes/1/react', [
            'emoji' => 'thumbsup',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_validates_allowed_reaction_emojis(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/react", [
            'emoji' => 'fire',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['emoji']);
    }

    #[Test]
    public function it_adds_a_reaction_and_returns_grouped_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $other->id,
        ]);

        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $other->id,
            'emoji' => 'thumbsup',
        ]);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/react", [
            'emoji' => 'thumbsup',
        ]);

        $response->assertOk();
        $response->assertJsonPath('reactions.thumbsup.count', 2);
        $response->assertJsonPath('reactions.thumbsup.reacted', true);
        $response->assertJsonPath('note_id', $note->id);
        $response->assertJsonPath('html', fn (string $html) => str_contains($html, 'reaction-toggle-form') && str_contains($html, '👍 2'));
        $this->assertDatabaseHas('note_reactions', [
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);
    }

    #[Test]
    public function it_removes_an_existing_reaction_when_toggled_again(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'eyes',
        ]);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/react", [
            'emoji' => 'eyes',
        ]);

        $response->assertOk();
        $response->assertJson(['reactions' => []]);
        $this->assertDatabaseMissing('note_reactions', [
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'eyes',
        ]);
    }

    #[Test]
    public function it_redirects_back_to_the_ticket_when_reacting_without_json(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->from("/tickets/{$ticket->id}")->post("/notes/{$note->id}/react", [
            'emoji' => 'thumbsup',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}#note_{$note->id}");
        $this->assertDatabaseHas('note_reactions', [
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_reply_to_a_note(): void
    {
        $response = $this->postJson('/notes/reply', [
            'ticket_id' => 1,
            'parent_id' => 1,
            'body' => 'Reply body',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_the_parent_note_to_belong_to_the_same_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $otherTicket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $parent = Note::factory()->create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body' => 'Reply body',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    #[Test]
    public function it_requires_the_parent_note_to_be_top_level(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $thread = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);
        $reply = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $thread->id,
        ]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $reply->id,
            'body' => 'Reply body',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    #[Test]
    public function it_creates_a_reply_with_markdown_and_mentions_and_returns_json(): void
    {
        $user = User::factory()->create(['name' => 'sarah']);
        $mentioned = User::factory()->create(['name' => 'alex']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $thread = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'notetype' => 'message',
        ]);

        $response = $this->actingAs($user)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
            'body' => '**Deploy** looks good, @[alex]',
        ]);

        $response->assertOk();
        $response->assertJsonPath('parent_id', $thread->id);
        $response->assertJsonPath('body_markdown', '**Deploy** looks good, @[alex]');
        $response->assertJsonPath('user.name', 'sarah');
        $response->assertJsonPath('ticket_id', $ticket->id);
        $this->assertStringContainsString('replies-section', $response->json('replies_html'));
        $this->assertStringContainsString('sarah', $response->json('replies_html'));

        $reply = Note::query()->where('ticket_id', $ticket->id)->where('parent_id', $thread->id)->latest('id')->first();

        $this->assertNotNull($reply);
        $this->assertStringContainsString('<strong>Deploy</strong>', $reply->body);
        $this->assertDatabaseHas('mentions', [
            'note_id' => $reply->id,
            'user_id' => $mentioned->id,
        ]);
    }

    #[Test]
    public function editing_a_note_creates_mentions_for_bracket_format(): void
    {
        $author = User::factory()->create();
        $mentioned = User::factory()->create(['name' => 'Alice Jones', 'title' => 'PM']);
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id]);

        $this->actingAs($author);

        $response = $this->putJson("/notes/{$note->id}", [
            'body' => 'Updated: @[Alice Jones (PM)] check this',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('mentions', [
            'note_id' => $note->id,
            'user_id' => $mentioned->id,
        ]);
    }

    #[Test]
    public function it_redirects_back_to_the_parent_thread_when_replying_without_json(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $thread = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'notetype' => 'message',
        ]);

        $response = $this->actingAs($user)->post('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
            'body' => 'Reply body',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}#note_{$thread->id}");
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
            'body_markdown' => 'Reply body',
        ]);
    }
}
