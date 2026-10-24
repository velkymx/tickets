<?php

namespace Tests\Feature\Kb;

use App\Models\KbArticle;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbVersionXssTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function version_history_strips_script_handlers(): void
    {
        $user = User::factory()->create();
        $article = KbArticle::factory()->create(['visibility' => 'public', 'status' => 'verified']);
        $article->versions()->create([
            'user_id' => $user->id,
            'version_number' => 99,
            'title' => 'x',
            'body_markdown' => '<img src=x onerror=alert(1)>',
            'body_html' => '<img src=x onerror=alert(1)>',
            'commit_message' => 'x',
        ]);

        $this->actingAs($user)->get("/kb/{$article->slug}/history/99")
            ->assertOk()
            ->assertDontSee('onerror', false);
    }
}
