<?php

namespace App\Snapshots;

use Carbon\CarbonImmutable;

/**
 * Reads the dedicated daily log of the snapshots (channel "snapshots").
 */
class SnapshotLog
{
    /**
     * Entries of the current day in the display timezone, newest first. Each
     * entry carries the tables whose number of rows changed since the
     * previous one.
     *
     * @return list<array{at: CarbonImmutable, level: string, message: string, context: array<string, mixed>, changes: array<string, int>}>
     */
    public function today(): array
    {
        $timezone = config('marche.timezone');
        $today = CarbonImmutable::now($timezone)->toDateString();
        $now = CarbonImmutable::now();

        // The files rotate at midnight in the application timezone, which may
        // not be the display one: yesterday's file can hold today's entries.
        $entries = [...$this->read($now->subDay()), ...$this->read($now)];

        $previous = null;

        foreach ($entries as $index => $entry) {
            $tables = $entry['context']['tables'] ?? null;

            if (is_array($tables)) {
                if ($previous !== null) {
                    foreach ($tables as $table => $count) {
                        $difference = (int) $count - (int) ($previous[$table] ?? 0);

                        if ($difference !== 0) {
                            $entries[$index]['changes'][$table] = $difference;
                        }
                    }
                }

                $previous = $tables;
            }
        }

        $entries = array_filter($entries, fn (array $entry): bool => $entry['at']->setTimezone($timezone)->toDateString() === $today);

        return array_reverse(array_values($entries));
    }

    /**
     * @return list<array{at: CarbonImmutable, level: string, message: string, context: array<string, mixed>, changes: array<string, int>}>
     */
    private function read(CarbonImmutable $day): array
    {
        $path = $this->path($day);

        if (! is_file($path)) {
            return [];
        }

        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [\w-]+\.(\w+): (.*?)(?: (\{.*\}))?\s*$/u', $line, $matches)) {
                continue;
            }

            $context = json_decode($matches[4] ?? '', true);

            $entries[] = [
                'at' => CarbonImmutable::parse($matches[1], config('app.timezone')),
                'level' => strtolower($matches[2]),
                'message' => $matches[3],
                'context' => is_array($context) ? $context : [],
                'changes' => [],
            ];
        }

        return $entries;
    }

    /**
     * Daily files are named after the channel path: snapshots-2026-11-20.log.
     */
    private function path(CarbonImmutable $day): string
    {
        $base = config('logging.channels.snapshots.path') ?? storage_path('logs/snapshots.log');

        return preg_replace('/\.log$/', '', $base).'-'.$day->format('Y-m-d').'.log';
    }
}
