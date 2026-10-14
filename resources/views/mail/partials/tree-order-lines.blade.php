<x-mail::table>
| {{ __('trees.pdf.article') }} | {{ __('trees.pdf.quantity') }} | {{ __('trees.summary.amount') }} |
|:--|--:|--:|
@foreach ($order->lines as $line)
| {{ $line->label }} ({{ $line->height }}) | {{ $line->quantity }} | {{ \App\Support\Money::format($line->amountCents()) }} |
@endforeach
| **{{ __('trees.form.total') }}** | | **{{ \App\Support\Money::format($order->total_cents) }}** |
</x-mail::table>
