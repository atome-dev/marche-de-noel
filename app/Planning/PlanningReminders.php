<?php

namespace App\Planning;

use App\Mail\StandPlanningReminder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reminds the managers of every stand with places still to fill, on request
 * of an administrator: one email per manager and per incomplete stand. A
 * stand reminded less than `reminder_interval_days` days ago is skipped.
 */
class PlanningReminders
{
    /**
     * @param  int|null  $standId  a single stand; null for every incomplete stand
     * @return int number of emails sent
     */
    public function send(User $sender, ?int $standId = null): int
    {
        $sent = 0;

        foreach ((new OpenPlacesSummary($standId === null ? null : [$standId]))->remindableRows() as $row) {
            foreach ($row['stand']->managers as $manager) {
                Mail::to($manager->email)->send(new StandPlanningReminder($manager, $row['stand']));
                $sent++;
            }

            // Spaces out the reminders: see marche.planning.reminder_interval_days.
            $row['stand']->forceFill(['planning_reminded_at' => now()])->save();
        }

        Log::info('Planning reminders sent.', ['admin' => $sender->id, 'stand' => $standId, 'emails' => $sent]);

        return $sent;
    }
}
