<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateUserRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
    #[Test]
    public function rules_match_persisted_fields_only(): void
    {
        $rules = (new UpdateUserRequest)->rules();

        $this->assertSame(
            ['name', 'email', 'phone', 'title', 'timezone', 'theme', 'bio', 'notify_mentions', 'notify_replies', 'notify_watching'],
            array_keys($rules)
        );
    }
}
