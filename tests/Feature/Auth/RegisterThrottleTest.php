<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class RegisterThrottleTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function registration_is_rate_limited(): void
    {
        // Route allows 5 requests/min; the 6th must be throttled.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [])->assertStatus(302); // validation redirect, not throttled
        }

        $this->post('/register', [])->assertStatus(429);
    }
}
