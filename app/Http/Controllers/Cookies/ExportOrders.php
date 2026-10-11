<?php

namespace App\Http\Controllers\Cookies;

use App\Cookies\OrdersSummary;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * XLSX and PDF exports of the purchasing summary.
 */
class ExportOrders extends Controller
{
    public function xlsx(OrdersSummary $summary): StreamedResponse
    {
        $ingredients = $summary->ingredients();

        return response()->streamDownload(function () use ($summary, $ingredients): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                __('cookies.orders.family'),
                __('fields.email'),
                ...array_map(fn (string $name) => __('cookies.orders.column_grams', ['ingredient' => $name]), array_values($ingredients)),
                __('cookies.orders.subtotal_grams'),
            ]));

            foreach ($summary->rows() as $row) {
                $writer->addRow(Row::fromValues([
                    $row['user']->last_name.' '.$row['user']->first_name,
                    $row['user']->email,
                    ...array_values($row['grams']),
                    $row['total'],
                ]));
            }

            $writer->addRow(Row::fromValues([
                __('cookies.orders.total'),
                '',
                ...array_values($summary->totals()),
                $summary->grandTotal(),
            ]));

            $writer->close();
        }, $this->filename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(OrdersSummary $summary): Response
    {
        return Pdf::loadView('cookies.orders-pdf', ['summary' => $summary])
            ->setPaper('a4', count($summary->ingredients()) > 3 ? 'landscape' : 'portrait')
            ->download($this->filename('pdf'));
    }

    private function filename(string $extension): string
    {
        return 'commandes-ingredients-'.now()->format('Y-m-d').'.'.$extension;
    }
}
