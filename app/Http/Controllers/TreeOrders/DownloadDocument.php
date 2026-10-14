<?php

namespace App\Http\Controllers\TreeOrders;

use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use App\TreeOrders\TreeOrderDocuments;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Order form and pickup slip of a tree order, as PDF.
 */
class DownloadDocument extends Controller
{
    public function orderForm(TreeOrder $treeOrder, TreeOrderDocuments $documents): Response
    {
        Gate::authorize('view', $treeOrder);

        return $documents->orderForm($treeOrder)
            ->download($documents->filename($treeOrder, TreeOrderDocuments::ORDER_FORM));
    }

    public function pickupSlip(TreeOrder $treeOrder, TreeOrderDocuments $documents): Response
    {
        Gate::authorize('view', $treeOrder);

        abort_unless($treeOrder->isPaid() && ! $treeOrder->isCancelled(), 404);

        return $documents->pickupSlip($treeOrder)
            ->download($documents->filename($treeOrder, TreeOrderDocuments::PICKUP_SLIP));
    }
}
