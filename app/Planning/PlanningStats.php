<?php

namespace App\Planning;

use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Headline figures of the planning, over the registrations of active stands.
 */
class PlanningStats
{
    /**
     * @var Collection<int, Registration>
     */
    private Collection $registrations;

    public function __construct()
    {
        $this->registrations = Registration::query()
            ->whereHas('slot.stand', fn ($stand) => $stand->where('is_active', true))
            ->with('slot')
            ->get();
    }

    /**
     * Distinct people on the planning: accounts, plus the people without an
     * account entered by a manager, told apart by their name.
     */
    public function volunteers(): int
    {
        return $this->registrations->whereNotNull('user_id')->unique('user_id')->count() + $this->guests();
    }

    public function guests(): int
    {
        return $this->registrations
            ->whereNull('user_id')
            ->unique(fn (Registration $registration): string => Str::lower(Str::squish((string) $registration->guest_name)))
            ->count();
    }

    /**
     * Total time on duty: two people on a 1:30 slot count for 3 hours.
     */
    public function minutes(): int
    {
        return (int) $this->registrations->sum(fn (Registration $registration): int => $registration->slot->durationInMinutes());
    }

    /**
     * The total time on duty as « 3 h » or « 3 h 30 ».
     */
    public function hoursLabel(): string
    {
        $minutes = $this->minutes();

        return $minutes % 60 === 0
            ? __('dashboard.stats.hours_value', ['hours' => intdiv($minutes, 60)])
            : __('dashboard.stats.hours_minutes_value', ['hours' => intdiv($minutes, 60), 'minutes' => sprintf('%02d', $minutes % 60)]);
    }
}
