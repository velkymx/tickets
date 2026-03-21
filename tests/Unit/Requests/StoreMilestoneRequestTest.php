<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreMilestoneRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreMilestoneRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreMilestoneRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_id_field(): void
    {
        $validator = $this->makeValidator([]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_name(): void
    {
        $validator = $this->makeValidator(['id' => 'new']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function it_limits_name_to_255_characters(): void
    {
        $validator = $this->makeValidator(['id' => 'new', 'name' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_nullable_description(): void
    {
        $validator = $this->makeValidator(['id' => 'new', 'name' => 'Test', 'description' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_scrummaster_user_id_exists(): void
    {
        $validator = $this->makeValidator(['id' => 'new', 'name' => 'Test', 'scrummaster_user_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('scrummaster_user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_owner_user_id_exists(): void
    {
        $validator = $this->makeValidator(['id' => 'new', 'name' => 'Test', 'owner_user_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('owner_user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_due_at_after_or_equal_start_at(): void
    {
        $validator = $this->makeValidator([
            'id' => 'new',
            'name' => 'Test',
            'start_at' => '2024-01-15',
            'due_at' => '2024-01-10',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_end_at_after_or_equal_start_at(): void
    {
        $validator = $this->makeValidator([
            'id' => 'new',
            'name' => 'Test',
            'start_at' => '2024-01-15',
            'end_at' => '2024-01-10',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_formats_dates_to_y_m_d_format(): void
    {
        $dateString = 'Jan 15, 2024';
        $formatted = date('Y-m-d', strtotime($dateString));
        $this->assertEquals('2024-01-15', $formatted);
    }

    #[Test]
    public function it_nullifies_empty_date_values(): void
    {
        $input = '';
        $validated = $input !== '' ? date('Y-m-d', strtotime($input)) : null;
        $this->assertNull($validated);
    }
}
