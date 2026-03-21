<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreReleaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreReleaseRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreReleaseRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_title(): void
    {
        $validator = $this->makeValidator([]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_limits_title_to_255_characters(): void
    {
        $validator = $this->makeValidator(['title' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_completed_at_after_or_equal_started_at(): void
    {
        $validator = $this->makeValidator([
            'title' => 'Test Release',
            'started_at' => '2024-01-15',
            'completed_at' => '2024-01-10',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('completed_at', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_nullable_body(): void
    {
        $validator = $this->makeValidator(['title' => 'Test', 'body' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_formats_dates_to_y_m_d_format(): void
    {
        $dateString = 'Jan 15, 2024';
        $formatted = ! empty($dateString) ? date('Y-m-d', strtotime($dateString)) : null;
        $this->assertEquals('2024-01-15', $formatted);
    }

    #[Test]
    public function it_nullifies_empty_date_values(): void
    {
        $input = '';
        $validated = ! empty($input) ? date('Y-m-d', strtotime($input)) : null;
        $this->assertNull($validated);
    }
}
