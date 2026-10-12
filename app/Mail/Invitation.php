<?php

namespace App\Mail;

use App\Models\Stand;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class Invitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $url, public ?string $personalMessage = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('invitations.mail.subject', ['name' => config('marche.name')]),
        );
    }

    public function content(): Content
    {
        $stands = $this->user->managedStands()->ordered()->get();

        return new Content(
            markdown: 'mail.invitation',
            with: [
                'intro' => $this->intro($stands),
                'purpose' => $stands->isEmpty() ? __('invitations.mail.purpose') : __('invitations.mail.purpose_manager'),
                'validityDays' => max(1, intdiv(config('marche.invitation_link_ttl'), 1440)),
            ],
        );
    }

    /**
     * "Vous êtes responsable du stand « Les petites bougies » du Marché de Noël."
     *
     * @param  Collection<int, Stand>  $stands
     */
    private function intro(Collection $stands): string
    {
        if ($stands->isEmpty()) {
            return __('invitations.mail.intro', ['market' => config('marche.name')]);
        }

        return trans_choice('invitations.mail.intro_manager', $stands->count(), [
            'stands' => $stands->map(fn (Stand $stand) => '« '.$stand->name.' »')->join(', ', ' '.__('invitations.mail.and').' '),
            'market' => config('marche.name'),
        ]);
    }
}
