<?php

namespace App\Services;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;

class SlashCommandService
{
    public function handle(Ticket $ticket, string $text): array
    {
        $lines = explode("\n", $text);
        $results = [
            'hours' => 0,
            'changes' => [],
            'actions' => [],
            'warnings' => [],
            'body' => '',
            'note_type' => 'message',
            'note_attributes' => [
                'pinned' => false,
            ],
        ];
        $bodyLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            if (! str_starts_with($trimmedLine, '/')) {
                $bodyLines[] = $trimmedLine;
                continue;
            }

            $parts = explode(' ', $trimmedLine, 2);
            $command = strtolower(substr($parts[0], 1));
            $args = trim($parts[1] ?? '');

            $result = $this->execute($ticket, $command, $args, $trimmedLine);
            if (isset($result['hours'])) {
                $results['hours'] += $result['hours'];
            }
            if (isset($result['changes'])) {
                $results['changes'] = array_merge($results['changes'], $result['changes']);
            }
            if (isset($result['actions'])) {
                $results['actions'] = array_merge($results['actions'], $result['actions']);
            }
            if (isset($result['warnings'])) {
                $results['warnings'] = array_merge($results['warnings'], $result['warnings']);
            }
            if (isset($result['body_lines'])) {
                $bodyLines = array_merge($bodyLines, $result['body_lines']);
            }
            if (isset($result['note_type'])) {
                $results['note_type'] = $result['note_type'];
            }
            if (isset($result['note_attributes'])) {
                $results['note_attributes'] = array_merge($results['note_attributes'], $result['note_attributes']);
            }
        }

        $results['body'] = trim(implode("\n", $bodyLines));

        if (count($results['actions']) > 0 || count($results['changes']) > 0) {
            (new TicketPulseService())->invalidatePulse($ticket->id);
        }

        return $results;
    }

    protected function execute(Ticket $ticket, string $command, string $args, string $rawLine): array
    {
        $result = [
            'changes' => [],
            'actions' => [],
            'warnings' => [],
            'body_lines' => [],
        ];

        switch ($command) {
            case 'status':
                if ($this->hasActiveBlocker($ticket)) {
                    $result['changes'][] = 'Resolve blocker before changing status';
                    break;
                }

                $status = $this->findByName(Status::class, $args);
                if ($status) {
                    $ticket->status_id = $status->id;
                    $ticket->save();
                    $result['changes'][] = "Status changed to {$status->name}";
                    $result['actions'][] = ['action' => 'status_changed', 'to' => $status->name];
                }
                break;

            case 'assign':
                $name = $this->parseMentionOrBareName($args);
                $user = $this->findByName(User::class, $name);
                if ($user) {
                    $ticket->user_id2 = $user->id;
                    $ticket->save();
                    $result['changes'][] = "Assigned to {$user->name}";
                    $result['actions'][] = ['action' => 'assigned', 'to' => $user->name];
                }
                break;

            case 'close':
                if ($this->hasActiveBlocker($ticket)) {
                    $result['changes'][] = 'Resolve blocker before changing status';
                    break;
                }

                $ticket->closed_at = now();
                $ticket->save();
                $result['changes'][] = 'Ticket closed';
                $result['actions'][] = ['action' => 'closed'];
                break;

            case 'reopen':
                $ticket->closed_at = null;
                $ticket->save();
                $result['changes'][] = 'Ticket reopened';
                $result['actions'][] = ['action' => 'reopened'];
                break;

            case 'estimate':
                $ticket->estimate = (int) $args;
                $ticket->save();
                $result['changes'][] = "Estimate changed to {$args}h";
                $result['actions'][] = ['action' => 'estimate_changed', 'to' => (int) $args];
                break;

            case 'hours':
                $result['hours'] = (float) $args;
                $result['actions'][] = ['action' => 'hours_logged', 'value' => (float) $args];
                break;

            case 'priority':
            case 'importance':
                $importance = $this->findByName(Importance::class, $args);
                if ($importance) {
                    $ticket->importance_id = $importance->id;
                    $ticket->save();
                    $result['changes'][] = "Importance changed to {$importance->name}";
                    $result['actions'][] = ['action' => 'importance_changed', 'to' => $importance->name];
                }
                break;

            case 'milestone':
                $milestone = $this->findByName(Milestone::class, $args);
                if ($milestone) {
                    $ticket->milestone_id = $milestone->id;
                    $ticket->save();
                    $result['changes'][] = "Milestone changed to {$milestone->name}";
                    $result['actions'][] = ['action' => 'milestone_changed', 'to' => $milestone->name];
                }
                break;

            case 'pin':
                $result['note_attributes'] = ['pinned' => true];
                $result['actions'][] = ['action' => 'pinned'];
                break;

            case 'decision':
            case 'blocker':
            case 'update':
                $result['note_type'] = $command;
                $result['body_lines'][] = $args;
                $result['actions'][] = ['action' => 'signal_set', 'to' => $command];
                break;

            case 'action':
                $mentions = $this->extractMentions($args);

                if (count($mentions) !== 1) {
                    $result['changes'][] = 'Actions require exactly one @assignee';
                    break;
                }

                if ($ticket->notes()->where('notetype', 'action')->where('resolved', false)->count() >= 3) {
                    $result['changes'][] = 'Too many open actions. Resolve or overwrite an existing action before creating a new one';
                    break;
                }

                $assigneeName = $mentions[0];
                $assignee = User::where('name', $assigneeName)->first();

                if ($assignee && ! $ticket->assignee) {
                    $ticket->user_id2 = $assignee->id;
                    $ticket->save();
                    $result['changes'][] = "Assigned to {$assignee->name}";
                    $result['actions'][] = ['action' => 'assigned', 'to' => $assignee->name];
                }

                $result['note_type'] = 'action';
                $result['body_lines'][] = $args;
                $result['actions'][] = ['action' => 'signal_set', 'to' => 'action'];
                break;

            default:
                $result['warnings'][] = "Unknown command: /{$command} — will be treated as text";
                $result['body_lines'][] = $rawLine;
                break;
        }

        return $result;
    }

    protected function hasActiveBlocker(Ticket $ticket): bool
    {
        return $ticket->notes()->where('notetype', 'blocker')->where('resolved', false)->exists();
    }

    protected function extractMentions(string $text): array
    {
        preg_match_all('/@\[([^\]]+)\]/u', $text, $matches);

        return array_values(array_unique(array_map(
            fn (string $token) => preg_replace('/\s*\([^)]*\)$/', '', trim($token)),
            $matches[1] ?? []
        )));
    }

    protected function findByName(string $class, string $value): mixed
    {
        $trimmed = trim($value);

        return $class::query()
            ->where('name', $trimmed)
            ->first()
            ?? $class::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->first()
            ?? $class::query()
                ->where('name', 'like', "%{$trimmed}%")
                ->orderByDesc('id')
                ->first();
    }

    protected function parseMentionOrBareName(string $args): string
    {
        // Try bracket format first: @[Name (Title)]
        if (preg_match('/@\[([^\]]+)\]/u', $args, $match)) {
            return preg_replace('/\s*\([^)]*\)$/', '', trim($match[1]));
        }

        // Fall back to bare name (strip leading @)
        return ltrim(trim($args), '@');
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
