<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTicketRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateTicketRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_allows_partial_updates_with_sometimes_rule(): void
    {
        $validator = $this->makeValidator(['subject' => 'New Subject']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_type_id_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['type_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_status_id_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['status_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_user_id2_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['user_id2' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id2', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_nullable_closed_at(): void
    {
        $validator = $this->makeValidator(['closed_at' => null]);
        $this->assertFalse($validator->fails());

        $validator = $this->makeValidator(['closed_at' => '2024-01-15']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_rejects_negative_storypoints(): void
    {
        $validator = $this->makeValidator(['storypoints' => -1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('storypoints', $validator->errors()->toArray());
    }

    #[Test]
    public function it_authorizes_authenticated_users(): void
    {
        $request = new UpdateTicketRequest;
        $this->assertTrue($request->authorize());
    }
}
