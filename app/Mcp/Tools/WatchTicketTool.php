<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Watch, unwatch, mute, or unmute a ticket for yourself. Watching sends you update notifications; muting keeps the watch but silences email. Any ticket may be watched, not just ones you own.')]
class WatchTicketTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'action' => 'nullable|in:watch,unwatch,mute,unmute',
        ]);

        // Any ticket is viewable, so watching is not limited to owned tickets.
        $ticket = Ticket::find($validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $action = $validated['action'] ?? 'watch';
        $watching = $this->applyWatch($ticket->id, $user->id, $action);

        return Response::json([
            'message' => 'Watch state updated.',
            'ticket_id' => $ticket->id,
            'watching' => $watching,
            'muted' => $action === 'mute',
        ]);
    }

    /**
     * Apply a watch action and return whether the user still watches the
     * ticket afterwards. Shared shape with the REST endpoint.
     */
    private function applyWatch(int $ticketId, int $userId, string $action): bool
    {
        $watcher = TicketUserWatcher::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->first();

        switch ($action) {
            case 'unwatch':
                $watcher?->delete();

                return false;

            case 'mute':
            case 'unmute':
                $watcher ??= new TicketUserWatcher(['ticket_id' => $ticketId, 'user_id' => $userId]);
                $watcher->muted = $action === 'mute';
                $watcher->save();

                return true;

            case 'watch':
            default:
                if (! $watcher) {
                    TicketUserWatcher::create(['ticket_id' => $ticketId, 'user_id' => $userId, 'muted' => false]);
                }

                return true;
        }
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'action' => $schema->string()->description('watch (default), unwatch, mute, or unmute.'),
        ];
    }
}
