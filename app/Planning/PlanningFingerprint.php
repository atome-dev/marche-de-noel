<?php

namespace App\Planning;

use Illuminate\Support\Facades\DB;

/**
 * Cheap signature of everything the planning shows, read in a single query.
 * Open planning pages compare it at each poll and only redraw when it changed.
 */
class PlanningFingerprint
{
    /**
     * Tables with timestamps: row count, highest id and last modification.
     */
    private const array TIMESTAMPED = ['registrations', 'slots', 'stands', 'users'];

    /**
     * Pivot tables without timestamps: row count and a checksum of their keys,
     * so that replacing one row by another is noticed too.
     *
     * @var array<string, array{string, string}>
     */
    private const array PIVOTS = [
        'stand_manager' => ['stand_id', 'user_id'],
        'school_class_stand' => ['school_class_id', 'stand_id'],
        'school_class_slot' => ['school_class_id', 'slot_id'],
    ];

    public function current(): string
    {
        $query = DB::query();

        foreach (self::TIMESTAMPED as $table) {
            $query->selectSub("select count(*) from {$table}", "{$table}_count")
                ->selectSub("select max(id) from {$table}", "{$table}_last")
                ->selectSub("select max(updated_at) from {$table}", "{$table}_updated");
        }

        foreach (self::PIVOTS as $table => [$first, $second]) {
            $query->selectSub("select count(*) from {$table}", "{$table}_count")
                ->selectSub("select sum({$first} * 31 + {$second}) from {$table}", "{$table}_sum");
        }

        return hash('xxh3', implode('|', array_map(strval(...), (array) $query->first())));
    }
}
