<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\FetchTicketsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchTicketsRequestTest extends TestCase
{
    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new FetchTicketsRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_a_date_range(): void
    {
        $validator = $this->makeValidator([]);

        $this->assertTrue($validator->errors()->has('started_at'));
        $this->assertTrue($validator->errors()->has('completed_at'));
    }

    #[Test]
    public function it_rejects_a_completed_at_before_started_at(): void
    {
        $validator = $this->makeValidator([
            'started_at' => '2024-06-30',
            'completed_at' => '2024-06-01',
        ]);

        $this->assertTrue($validator->errors()->has('completed_at'));
    }

    #[Test]
    public function it_accepts_a_valid_date_range(): void
    {
        $validator = $this->makeValidator([
            'started_at' => '2024-06-01',
            'completed_at' => '2024-06-30',
        ]);

        $this->assertFalse($validator->fails());
    }
}
