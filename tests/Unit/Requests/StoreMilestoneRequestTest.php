<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreMilestoneRequest;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreMilestoneRequestTest extends TestCase
{
    use SeedsDatabase;

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
        $request = new StoreMilestoneRequest;
        $request->merge([
            'id' => 'new',
            'name' => 'Test',
            'start_at' => 'Jan 15, 2024 10:30:00',
            'due_at' => '2024/01/20',
            'end_at' => '2024-01-25 15:00:00',
        ]);
        $request->setContainer($this->app);
        $request->setValidator(Validator::make(
            $request->all(),
            $request->rules(),
        ));

        $validated = $request->validated();

        $this->assertEquals('2024-01-15', $validated['start_at']);
        $this->assertEquals('2024-01-20', $validated['due_at']);
        $this->assertEquals('2024-01-25', $validated['end_at']);
    }

    #[Test]
    public function it_nullifies_empty_date_values(): void
    {
        $request = new StoreMilestoneRequest;
        $request->merge([
            'id' => 'new',
            'name' => 'Test',
            'start_at' => '',
            'due_at' => '',
            'end_at' => '',
        ]);
        $request->setContainer($this->app);
        $request->setValidator(Validator::make(
            $request->all(),
            $request->rules(),
        ));

        $validated = $request->validated();

        $this->assertNull($validated['start_at']);
        $this->assertNull($validated['due_at']);
        $this->assertNull($validated['end_at']);
    }

    #[Test]
    public function it_passes_through_valid_date_strings(): void
    {
        $request = new StoreMilestoneRequest;
        $request->merge([
            'id' => 'new',
            'name' => 'Test',
            'start_at' => '2024-06-15',
            'due_at' => '2024-06-20',
            'end_at' => '2024-06-30',
        ]);
        $request->setContainer($this->app);
        $request->setValidator(Validator::make(
            $request->all(),
            $request->rules(),
        ));

        $validated = $request->validated();

        $this->assertEquals('2024-06-15', $validated['start_at']);
        $this->assertEquals('2024-06-20', $validated['due_at']);
        $this->assertEquals('2024-06-30', $validated['end_at']);
    }
}
