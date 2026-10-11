<?php

namespace App\Raffle;

use App\Models\RafflePrize;
use Illuminate\Support\Facades\DB;

/**
 * The ordered list of raffle prizes.
 *
 * While the list is a draft, a prize number is its rank and prizes can be
 * reordered. Once the list is validated the numbers are frozen and every new
 * prize takes the next number.
 */
class PrizeList
{
    public function isValidated(): bool
    {
        return RafflePrize::query()->whereNotNull('number')->exists();
    }

    public function add(string $label): RafflePrize
    {
        return DB::transaction(function () use ($label): RafflePrize {
            if (! $this->isValidated()) {
                return RafflePrize::create([
                    'label' => $label,
                    'position' => (int) RafflePrize::max('position') + 1,
                ]);
            }

            $number = (int) RafflePrize::max('number') + 1;

            return RafflePrize::create(['label' => $label, 'position' => $number, 'number' => $number]);
        });
    }

    /**
     * Move a prize to a rank (1 = most important). Ignored once validated.
     */
    public function move(RafflePrize $prize, int $rank): void
    {
        if ($this->isValidated()) {
            return;
        }

        DB::transaction(function () use ($prize, $rank): void {
            $ids = RafflePrize::query()->ordered()->pluck('id')->reject(fn (int $id) => $id === $prize->id)->values()->all();

            array_splice($ids, max(0, min($rank - 1, count($ids))), 0, [$prize->id]);

            $this->renumber($ids);
        });
    }

    public function delete(RafflePrize $prize): void
    {
        DB::transaction(function () use ($prize): void {
            $wasValidated = $this->isValidated();

            $prize->delete();

            // Frozen numbers keep their gap; a draft list stays numbered 1 to n.
            if (! $wasValidated) {
                $this->renumber(RafflePrize::query()->ordered()->pluck('id')->all());
            }
        });
    }

    /**
     * Freeze the numbers. Returns false when there is nothing to validate.
     */
    public function validate(): bool
    {
        if ($this->isValidated() || RafflePrize::query()->doesntExist()) {
            return false;
        }

        DB::transaction(function (): void {
            $ids = RafflePrize::query()->ordered()->pluck('id')->all();

            $this->renumber($ids);

            foreach ($ids as $index => $id) {
                RafflePrize::whereKey($id)->update(['number' => $index + 1]);
            }
        });

        return true;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function renumber(array $ids): void
    {
        foreach (array_values($ids) as $index => $id) {
            RafflePrize::whereKey($id)->update(['position' => $index + 1]);
        }
    }
}
