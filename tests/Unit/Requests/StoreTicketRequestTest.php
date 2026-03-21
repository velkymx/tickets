<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTicketRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreTicketRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreTicketRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_subject(): void
    {
        $validator = $this->makeValidator([]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('subject', $validator->errors()->toArray());
    }

    #[Test]
    public function it_limits_subject_to_255_characters(): void
    {
        $validator = $this->makeValidator(['subject' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('subject', $validator->errors()->toArray());

        $validator = $this->makeValidator(['subject' => str_repeat('a', 255)]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_requires_type_id_to_exist_in_types_table(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'type_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_status_id_to_exist(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'status_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_importance_id_to_exist(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'importance_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('importance_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_milestone_id_to_exist(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'milestone_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('milestone_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_project_id_to_exist(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'project_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_nullable_description(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'description' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_allows_nullable_user_id2(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_allows_nullable_due_at_as_date(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'due_at' => '2024-01-15']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_rejects_non_date_due_at(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'due_at' => 'not-a-date']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_nullable_estimate_with_min_zero(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'estimate' => 0]);
        $this->assertFalse($validator->fails());

        $validator = $this->makeValidator(['subject' => 'Test', 'estimate' => 5.5]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_rejects_negative_estimate(): void
    {
        $validator = $this->makeValidator(['subject' => 'Test', 'estimate' => -1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('estimate', $validator->errors()->toArray());
    }

    #[Test]
    public function it_authorizes_authenticated_users(): void
    {
        $request = new StoreTicketRequest;
        $this->assertTrue($request->authorize());
    }
}
