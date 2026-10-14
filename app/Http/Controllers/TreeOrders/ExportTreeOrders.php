<?php

namespace App\Http\Controllers\TreeOrders;

use App\Http\Controllers\Controller;
use App\Models\TreeOrderLine;
use App\TreeOrders\TreeOrdersSummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * XLSX and PDF exports of the producer summary.
 */
class ExportTreeOrders extends Controller
{
    public function xlsx(TreeOrdersSummary $summary): StreamedResponse
    {
        return response()->streamDownload(function () use ($summary): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName(__('trees.summary.title'));

            $writer->addRow(Row::fromValues([__('trees.summary.size'), __('trees.summary.quantity'), __('trees.summary.amount')]));

            foreach ($summary->rows() as $row) {
                $writer->addRow(Row::fromValues([$row['label'].' ('.$row['height'].')', $row['quantity'], $row['amount_cents'] / 100]));
            }

            $writer->addRow(Row::fromValues([__('trees.summary.total'), $summary->totalQuantity(), $summary->totalAmountCents() / 100]));

            $writer->addNewSheetAndMakeItCurrent()->setName(__('trees.summary.orders'));

            $sizes = array_column($summary->rows(), 'label', 'key');

            $writer->addRow(Row::fromValues([
                __('trees.pdf.number', ['number' => '']),
                __('trees.manage.customer'),
                __('trees.pdf.phone'),
                __('trees.pdf.email'),
                ...array_values($sizes),
                __('trees.summary.amount'),
                __('trees.manage.payment'),
            ]));

            foreach ($summary->paidOrders() as $order) {
                $quantities = $order->lines->mapWithKeys(fn (TreeOrderLine $line) => [$line->size_key => $line->quantity]);

                $writer->addRow(Row::fromValues([
                    $order->number,
                    $order->customer_name,
                    $order->customer_phone,
                    (string) $order->customer_email,
                    ...array_map(fn (string $key) => (int) ($quantities[$key] ?? 0), array_keys($sizes)),
                    $order->total_cents / 100,
                    (string) $order->payment_method?->label(),
                ]));
            }

            $writer->close();
        }, $this->filename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(TreeOrdersSummary $summary): Response
    {
        return Pdf::loadView('tree-orders.summary-pdf', ['summary' => $summary])
            ->setPaper('a4')
            ->download($this->filename('pdf'));
    }

    private function filename(string $extension): string
    {
        return __('trees.summary.filename').'-'.now()->format('Y-m-d').'.'.$extension;
    }
}
