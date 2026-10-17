<?php

namespace Tests\Unit\Models;

use App\Models\NoteReaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NoteReactionTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_limits_v21_to_the_allowed_emojis(): void
    {
        $this->assertSame(['thumbsup', 'eyes'], NoteReaction::ALLOWED_EMOJIS);
    }
}
