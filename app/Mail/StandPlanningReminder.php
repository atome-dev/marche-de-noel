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
        $days = $this->stand->slots()
            ->where('date', '>=', PlanningGrid::today())
            ->where('capacity', '>', 0)
            ->with('registrations.user')
            ->get()
            ->groupBy(fn (Slot $slot): string => OpenPlacesSummary::dayLabel($slot->date->toDateString()));

        return new Content(
            markdown: 'mail.stand-planning-reminder',
            with: ['days' => $days],
        );
    }
}
