<?php

namespace App\Mail;

use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoShop;
use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeOrderDocuments;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Summary of a new tree order, with the order form to hand in with a cheque.
 */
class TreeOrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TreeOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('trees.mail.placed.subject', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tree-order-placed',
            with: [
                'deadline' => app(TreeCatalog::class)->deadline(),
                'onlinePayment' => $this->order->user_id !== null,
                'shopUrl' => $this->order->user_id !== null ? app(HelloAssoShop::class)->url() : null,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $documents = app(TreeOrderDocuments::class);

        return [
            Attachment::fromData(fn () => $documents->orderForm($this->order)->output(), $documents->filename($this->order, TreeOrderDocuments::ORDER_FORM))
                ->withMime('application/pdf'),
        ];
    }
}
