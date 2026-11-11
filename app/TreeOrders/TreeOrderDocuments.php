<?php

namespace App\TreeOrders;

use App\Models\TreeOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Printable order form (to hand in with a cheque) and pickup slip.
 */
class TreeOrderDocuments
{
    public const ORDER_FORM = 'bon-de-commande';

    public const PICKUP_SLIP = 'bon-de-retrait';

    public function orderForm(TreeOrder $order): DomPdf
    {
        return Pdf::loadView('tree-orders.order-form-pdf', ['order' => $order->loadMissing('lines')])
            ->setPaper('a4');
    }

    /**
     * Unnumbered form filled in by hand, handed in with the payment; the
     * buyer keeps the pickup slip at the bottom.
     */
    public function blankOrderForm(): DomPdf
    {
        return Pdf::loadView('tree-orders.blank-order-form-pdf', ['sizes' => app(TreeCatalog::class)->sizes()])
            ->setPaper('a4');
    }

    public function blankOrderFormFilename(): string
    {
        return self::ORDER_FORM.'-sapins.pdf';
    }

    public function pickupSlip(TreeOrder $order): DomPdf
    {
        return Pdf::loadView('tree-orders.pickup-slip-pdf', ['order' => $order->loadMissing('lines')])
            ->setPaper('a5', 'landscape');
    }

    public function filename(TreeOrder $order, string $document): string
    {
        return $document.'-'.$order->number.'.pdf';
    }
}
