<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTicketRequest;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTicketRequestTest extends TestCase
{
    use SeedsDatabase;

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
    public function it_validates_description_is_nullable_string(): void
    {
        $validator = $this->makeValidator(['description' => null]);
        $this->assertFalse($validator->fails());

        $validator = $this->makeValidator(['description' => 'Updated description']);
        $this->assertFalse($validator->fails());

        $validator = $this->makeValidator(['description' => 123]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_validates_importance_id_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['importance_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('importance_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_milestone_id_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['milestone_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('milestone_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_project_id_exists_when_provided(): void
    {
        $validator = $this->makeValidator(['project_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_due_at_is_date(): void
    {
        $validator = $this->makeValidator(['due_at' => 'not-a-date']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_estimate_is_numeric_min_zero(): void
    {
        $validator = $this->makeValidator(['estimate' => -1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('estimate', $validator->errors()->toArray());

        $validator = $this->makeValidator(['estimate' => 5.5]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_actual_is_numeric_min_zero(): void
    {
        $validator = $this->makeValidator(['actual' => -1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('actual', $validator->errors()->toArray());

        $validator = $this->makeValidator(['actual' => 10.5]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_limits_subject_to_255_characters(): void
    {
        $validator = $this->makeValidator(['subject' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('subject', $validator->errors()->toArray());
    }

    #[Test]
    public function it_authorizes_authenticated_users(): void
    {
        $request = new UpdateTicketRequest;
        $this->assertTrue($request->authorize());
    }
}
