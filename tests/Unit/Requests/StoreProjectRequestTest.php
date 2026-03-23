<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProjectRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class StoreProjectRequestTest extends TestCase
{
    use SeedsDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreProjectRequest;

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
}
