<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\EstimateTicketRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class EstimateTicketRequestTest extends TestCase
{
    use SeedsDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new EstimateTicketRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_storypoints(): void
    {
        $validator = $this->makeValidator([]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('storypoints', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_storypoints_to_be_integer(): void
    {
        $validator = $this->makeValidator(['storypoints' => 'abc']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('storypoints', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_storypoints_min_zero(): void
    {
        $validator = $this->makeValidator(['storypoints' => -1]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('storypoints', $validator->errors()->toArray());

        $validator = $this->makeValidator(['storypoints' => 0]);
        $this->assertFalse($validator->fails());

        $validator = $this->makeValidator(['storypoints' => 5]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_authorizes_authenticated_users(): void
    {
        $request = new EstimateTicketRequest;
        $this->assertTrue($request->authorize());
    }
}
