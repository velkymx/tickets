<?php

namespace App\Services;

/**
 * Parses a GitLab/Sentry-style query string into Ticket::scopeFilter params.
 *
 * Grammar: space-separated key:value tokens (values may be "quoted") plus free
 * text. Recognised keys: status/is, importance, type, project, milestone,
 * assignee. Unknown keys or unresolved values are ignored (play dumb); leftover
 * bare words become a subject search.
 */
class TicketQueryParser
{
    public function __construct(private TicketService $ticketService)
    {
    }

    /**
     * @return array<string, int|string> scopeFilter params
     */
    public function parse(string $query): array
    {
        $filters = [];
        $free = [];

        foreach ($this->split($query) as $part) {
            if (preg_match('/^(\w+):(.+)$/', $part, $m)) {
                if ($this->applyToken($filters, strtolower($m[1]), $this->unquote($m[2]))) {
                    continue;
                }
                // Unknown key or unresolved value: ignore it entirely.
                continue;
            }
            $free[] = $part;
        }

        if ($free !== []) {
            $filters['q'] = implode(' ', $free);
        }

        return $filters;
    }

    /**
     * Recognised filter tokens (for rendering removable chips).
     *
     * @return array<int, array{key: string, value: string, raw: string}>
     */
    public function tokenize(string $query): array
    {
        $tokens = [];

        foreach ($this->split($query) as $part) {
            if (preg_match('/^(\w+):(.+)$/', $part, $m)) {
                $probe = [];
                if ($this->applyToken($probe, strtolower($m[1]), $this->unquote($m[2]))) {
                    $tokens[] = ['key' => strtolower($m[1]), 'value' => $this->unquote($m[2]), 'raw' => $part];
                }
            }
        }

        return $tokens;
    }

    private function applyToken(array &$filters, string $key, string $value): bool
    {
        $v = strtolower(trim($value));

        return match ($key) {
            'is' => $this->applyStatus($filters, $v, allowNames: false),
            'status' => $this->applyStatus($filters, $v, allowNames: true),
            'importance' => $this->setLookup($filters, 'importance_id', 'importances', $v),
            'type' => $this->setLookup($filters, 'type_id', 'types', $v),
            'project' => $this->setResolved($filters, 'project_id', 'projects', $value),
            'milestone' => $this->setResolved($filters, 'milestone_id', 'milestones', $value),
            'assignee' => $this->applyAssignee($filters, $v),
            default => false,
        };
    }

    private function applyStatus(array &$filters, string $v, bool $allowNames): bool
    {
        if (in_array($v, ['active', 'open'], true)) {
            $filters['status_id'] = 'none';

            return true;
        }
        if ($v === 'closed') {
            $filters['status_id'] = 'closed';

            return true;
        }

        return $allowNames && $this->setLookup($filters, 'status_id', 'statuses', $v);
    }

    private function applyAssignee(array &$filters, string $v): bool
    {
        if ($v === 'me') {
            $filters['assignee'] = 'me';

            return true;
        }

        return $this->setLookup($filters, 'user_id2', 'users', $v);
    }

    private function setLookup(array &$filters, string $param, string $lookupKey, string $lowerValue): bool
    {
        $id = $this->map($lookupKey)[$lowerValue] ?? null;
        if ($id === null) {
            return false;
        }
        $filters[$param] = $id;

        return true;
    }

    private function setResolved(array &$filters, string $param, string $lookupKey, string $value): bool
    {
        $value = trim($value);
        if (is_numeric($value)) {
            $filters[$param] = (int) $value;

            return true;
        }

        return $this->setLookup($filters, $param, $lookupKey, strtolower($value));
    }

    /** @return array<string, int> lowercased-name => id */
    private function map(string $lookupKey): array
    {
        $out = [];
        foreach (($this->ticketService->getLookups()[$lookupKey] ?? []) as $id => $name) {
            $out[strtolower((string) $name)] = $id;
        }

        return $out;
    }

    /** @return array<int, string> */
    private function split(string $query): array
    {
        preg_match_all('/\S+:"[^"]*"|"[^"]*"|\S+/', trim($query), $m);

        return $m[0];
    }

    private function unquote(string $value): string
    {
        return trim($value, '"');
    }
}
