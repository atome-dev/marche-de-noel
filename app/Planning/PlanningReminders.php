<?php

namespace App\Planning;

use App\Mail\StandPlanningReminder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reminds the managers of every stand with places still to fill, on request
 * of an administrator: one email per manager and per incomplete stand.
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

        foreach ((new OpenPlacesSummary($standId === null ? null : [$standId]))->incompleteRows() as $row) {
            foreach ($row['stand']->managers as $manager) {
                Mail::to($manager->email)->send(new StandPlanningReminder($manager, $row['stand']));
                $sent++;
            }
        }

        Log::info('Planning reminders sent.', ['admin' => $sender->id, 'stand' => $standId, 'emails' => $sent]);

        return $sent;
    }
}
