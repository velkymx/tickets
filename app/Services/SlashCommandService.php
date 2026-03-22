<?php

namespace App\Services;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Importance;

class SlashCommandService
{
    public function handle(Ticket $ticket, string $text): array
    {
        $lines = explode("\n", $text);
        $results = [
            'hours' => 0,
            'changes' => [],
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (! str_starts_with($line, '/')) {
                continue;
            }

            $parts = explode(' ', $line, 2);
            $command = substr($parts[0], 1);
            $args = trim($parts[1] ?? '');

            $result = $this->execute($ticket, $command, $args);
            if (isset($result['hours'])) {
                $results['hours'] += $result['hours'];
            }
            if (isset($result['change'])) {
                $results['changes'][] = $result['change'];
            }
        }

        if (count($results['changes']) > 0) {
            (new TicketPulseService())->invalidatePulse($ticket->id);
        }

        return $results;
    }

    protected function execute(Ticket $ticket, string $command, string $args): array
    {
        $result = [];

        switch ($command) {
            case 'status':
                $status = Status::where('name', 'like', "%{$args}%")->first();
                if ($status) {
                    $ticket->status_id = $status->id;
                    $ticket->save();
                    $result['change'] = "Status changed to {$status->name}";
                }
                break;

            case 'assign':
                $username = ltrim($args, '@');
                $user = User::where('name', $username)->first();
                if ($user) {
                    $ticket->user_id2 = $user->id;
                    $ticket->save();
                    $result['change'] = "Assigned to {$user->name}";
                }
                break;

            case 'close':
                $ticket->closed_at = now();
                $ticket->save();
                $result['change'] = "Ticket closed";
                break;

            case 'reopen':
                $ticket->closed_at = null;
                $ticket->save();
                $result['change'] = "Ticket reopened";
                break;

            case 'estimate':
                $ticket->estimate = (int) $args;
                $ticket->save();
                $result['change'] = "Estimate changed to {$args}h";
                break;

            case 'hours':
                $result['hours'] = (float) $args;
                break;

            case 'priority':
            case 'importance':
                $importance = Importance::where('name', 'like', "%{$args}%")->first();
                if ($importance) {
                    $ticket->importance_id = $importance->id;
                    $ticket->save();
                    $result['change'] = "Importance changed to {$importance->name}";
                }
                break;
        }

        return $result;
    }

    public function getSignalType(string $text): string
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '/decision')) return 'decision';
            if (str_starts_with($line, '/blocker')) return 'blocker';
            if (str_starts_with($line, '/update')) return 'update';
            if (str_starts_with($line, '/action')) return 'action';
        }

        return 'message';
    }
}
