<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\FetchTicketsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class FetchTicketsRequestTest extends TestCase
{
    use SeedsDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new FetchTicketsRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_accepts_dropdown_perpage_values(): void
    {
        foreach ([10, 20, 30, 40, 50] as $value) {
            $validator = $this->makeValidator(['perpage' => $value]);
            $this->assertFalse(
                $validator->errors()->has('perpage'),
                "perpage={$value} should be accepted"
            );
        }
    }

    #[Test]
    public function it_rejects_perpage_values_not_in_dropdown(): void
    {
        foreach ([25, 100, 15, 999] as $value) {
            $validator = $this->makeValidator(['perpage' => $value]);
            $this->assertTrue(
                $validator->errors()->has('perpage'),
                "perpage={$value} should be rejected"
            );
        }
    }
}
