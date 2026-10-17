<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * Classifies tickets into an Action Priority (Impact-Effort) Matrix.
 *
 * Impact  = ticket importance. High impact is critical/blocker (importance_id >= 4).
 * Effort  = story points rounded up to the nearest Fibonacci band,
 *           falling back to the hour estimate when no points exist, since
 *           points are the primary estimate on a ticket. Low effort is
 *           band <= 5; high effort is band >= 8. Tickets with neither
 *           estimate are grouped separately so they can be estimated.
 */
class PriorityMatrixService
{
    public const QUICK_WINS = 'quick_wins';
    public const MAJOR_PROJECTS = 'major_projects';
    public const FILL_INS = 'fill_ins';
    public const THANKLESS = 'thankless';
    public const UNESTIMATED = 'unestimated';

    /** Importance id at or above which a ticket is "high impact". */
    private const HIGH_IMPACT_MIN = 4;

    /** Effort band (in Fibonacci hours) at or above which effort is "high". */
    private const HIGH_EFFORT_BAND = 8;

    private const FIBONACCI = [1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144];

    /**
     * Group a set of tickets into the five matrix buckets, each sorted by
     * importance (desc) then estimate (desc).
     *
     * @return array<string, Collection<int, Ticket>>
     */
    public function classify(iterable $tickets): array
    {
        $buckets = [
            self::QUICK_WINS => collect(),
            self::MAJOR_PROJECTS => collect(),
            self::FILL_INS => collect(),
            self::THANKLESS => collect(),
            self::UNESTIMATED => collect(),
        ];

        foreach ($tickets as $ticket) {
            $buckets[$this->quadrantFor($ticket)]->push($ticket);
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key] = $bucket
                ->sortBy([
                    ['importance_id', 'desc'],
                    [fn ($ticket) => $this->effortValue($ticket), 'desc'],
                ])
                ->values();
        }

        return $buckets;
    }

    /**
     * Effective effort figure: story points first, hour estimate as fallback.
     */
    public function effortValue(Ticket $ticket): float
    {
        $points = (float) ($ticket->storypoints ?? 0);

        if ($points > 0) {
            return $points;
        }

        return (float) $ticket->estimate;
    }

    /**
     * Determine which matrix bucket a single ticket belongs to.
     */
    public function quadrantFor(Ticket $ticket): string
    {
        $estimate = $this->effortValue($ticket);

        if ($estimate <= 0) {
            return self::UNESTIMATED;
        }

        $highImpact = (int) $ticket->importance_id >= self::HIGH_IMPACT_MIN;
        $highEffort = $this->effortBand($estimate) >= self::HIGH_EFFORT_BAND;

        return match (true) {
            $highImpact && ! $highEffort => self::QUICK_WINS,
            $highImpact && $highEffort => self::MAJOR_PROJECTS,
            ! $highImpact && ! $highEffort => self::FILL_INS,
            default => self::THANKLESS,
        };
    }

    /**
     * Round an estimate (hours) up to the nearest Fibonacci band.
     */
    public function effortBand(float $estimate): int
    {
        foreach (self::FIBONACCI as $band) {
            if ($estimate <= $band) {
                return $band;
            }
        }

        return self::FIBONACCI[count(self::FIBONACCI) - 1];
    }
}
