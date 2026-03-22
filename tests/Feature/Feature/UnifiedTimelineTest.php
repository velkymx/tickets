<?php

namespace Tests\Feature\Feature;

use Tests\Traits\SeedsDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UnifiedTimelineTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
