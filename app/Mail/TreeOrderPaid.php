<?php

namespace App\Mail;

use App\Models\TreeOrder;
use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeOrderDocuments;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Payment confirmation of a tree order, with the pickup slip.
 */
class TreeOrderPaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TreeOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('trees.mail.paid.subject', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tree-order-paid',
            with: ['pickupDate' => app(TreeCatalog::class)->pickupDate()],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $documents = app(TreeOrderDocuments::class);

        return [
            Attachment::fromData(fn () => $documents->pickupSlip($this->order)->output(), $documents->filename($this->order, TreeOrderDocuments::PICKUP_SLIP))
                ->withMime('application/pdf'),
        ];
    }
}
