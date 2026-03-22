<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BatchUpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BatchUpdateTicketRequestTest extends TestCase
{
    use SeedsDatabase;

    protected function makeValidator(array $data): \Illuminate\Validation\Validator
    {
        $request = new BatchUpdateTicketRequest;

        return Validator::make($data, $request->rules());
    }

    #[Test]
    public function it_requires_tickets_array(): void
    {
        $validator = $this->makeValidator([]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tickets', $validator->errors()->toArray());
    }

    #[Test]
    public function it_requires_at_least_one_ticket(): void
    {
        $validator = $this->makeValidator(['tickets' => []]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_validates_each_ticket_exists(): void
    {
        $validator = $this->makeValidator(['tickets' => [999, 998]]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_allows_nullable_type_id(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $validator = $this->makeValidator(['tickets' => [$ticket->id], 'type_id' => null, 'status_id' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_allows_nullable_status_id(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $validator = $this->makeValidator(['tickets' => [$ticket->id], 'status_id' => null, 'type_id' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_allows_nullable_release_id(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $validator = $this->makeValidator(['tickets' => [$ticket->id], 'release_id' => null, 'type_id' => null]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_release_id_exists_when_provided(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $validator = $this->makeValidator(['tickets' => [$ticket->id], 'release_id' => 999]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('release_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_authorizes_authenticated_users(): void
    {
        $request = new BatchUpdateTicketRequest;
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function it_returns_custom_error_messages(): void
    {
        $request = new BatchUpdateTicketRequest;
        $messages = $request->messages();

        $this->assertEquals('At least one ticket must be selected.', $messages['tickets.required']);
        $this->assertEquals('One or more selected tickets do not exist.', $messages['tickets.*.exists']);
    }
}
