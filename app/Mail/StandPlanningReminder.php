<?php

namespace App\Mail;

use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\OpenPlacesSummary;
use App\Planning\PlanningGrid;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Asks a manager to find volunteers for their stand, with its current planning.
 */
class StandPlanningReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $manager, public Stand $stand) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('stats.reminder.mail.subject', ['name' => config('marche.name'), 'stand' => $this->stand->name]),
        );
    }

    public function content(): Content
    {
        $slots = $this->stand->slots()
            ->where('date', '>=', PlanningGrid::today())
            ->where('capacity', '>', 0)
            ->with('registrations.user')
            ->get();

        // Overbooked slots do not make up for the others.
        $openPlaces = $slots->map(fn (Slot $slot): int => max(0, $slot->capacity - $slot->takenCount()));
        $placesCount = (int) $openPlaces->sum();
        $slotsCount = $openPlaces->filter()->count();

        return new Content(
            markdown: 'mail.stand-planning-reminder',
            with: [
                'days' => $slots->groupBy(fn (Slot $slot): string => OpenPlacesSummary::dayLabel($slot->date->toDateString())),
                'openSummary' => __('stats.reminder.mail.open_summary', [
                    'places' => trans_choice('stats.reminder.mail.open_places', $placesCount, ['count' => $placesCount]),
                    'slots' => trans_choice('stats.reminder.mail.open_slots', $slotsCount, ['count' => $slotsCount]),
                ]),
            ],
        );
    }
}
