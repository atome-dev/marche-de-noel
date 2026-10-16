<?php

namespace App\TreeOrders\HelloAsso;

use App\Exceptions\HelloAssoUnavailable;
use App\Models\TreeOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The few calls of the HelloAsso API v5 used by the tree orders: an OAuth2
 * token (client credentials), creating and reading checkout intents, and
 * reading the event ticketing of the concert.
 */
class HelloAssoClient
{
    private const TOKEN_CACHE_KEY = 'helloasso.token';

    /** Reads shown in a page wait less than a payment. */
    private const READ_TIMEOUT = 5;

    public function isConfigured(): bool
    {
        return filled(config('trees.helloasso.client_id'))
            && filled(config('trees.helloasso.client_secret'))
            && filled(config('trees.helloasso.organization_slug'));
    }

    /**
     * @return array{id: int, redirectUrl: string}
     *
     * @throws HelloAssoUnavailable
     */
    public function createCheckout(TreeOrder $order, string $returnUrl, string $backUrl, string $errorUrl): array
    {
        $response = $this->send(fn (PendingRequest $request) => $request->post($this->organizationUrl('checkout-intents'), [
            'totalAmount' => $order->total_cents,
            'initialAmount' => $order->total_cents,
            'itemName' => mb_substr(__('trees.helloasso.item_name', ['number' => $order->number]), 0, 250),
            'backUrl' => $backUrl,
            'errorUrl' => $errorUrl,
            'returnUrl' => $returnUrl,
            'containsDonation' => false,
            // Names breaking the strict rules of HelloAsso are left to the payer.
            'payer' => [
                ...array_filter(['email' => $order->customer_email]),
                ...($order->user ? HelloAssoPayerName::fields((string) $order->user->first_name, (string) $order->user->last_name) : []),
            ],
            'metadata' => ['tree_order_id' => $order->id, 'number' => $order->number],
        ]));

        return ['id' => (int) $response['id'], 'redirectUrl' => (string) $response['redirectUrl']];
    }

    /**
     * Checkout intent with its "order" once paid.
     *
     * @return array<string, mixed>
     *
     * @throws HelloAssoUnavailable
     */
    public function fetchCheckout(int $checkoutId): array
    {
        return $this->send(fn (PendingRequest $request) => $request->get($this->organizationUrl('checkout-intents/'.$checkoutId)));
    }

    /**
     * Public description of an event ticketing (title, date, place, prices).
     *
     * @return array<string, mixed>
     *
     * @throws HelloAssoUnavailable
     */
    public function fetchEventForm(string $formSlug): array
    {
        return $this->send(fn (PendingRequest $request) => $request->get($this->organizationUrl('forms/Event/'.$formSlug.'/public')), self::READ_TIMEOUT);
    }

    /**
     * Every ticket sold by an event ticketing, with its payer and payments.
     *
     * @return list<array<string, mixed>>
     *
     * @throws HelloAssoUnavailable
     */
    public function fetchEventItems(string $formSlug): array
    {
        $items = [];
        $page = 1;

        do {
            $response = $this->send(fn (PendingRequest $request) => $request->get($this->organizationUrl('forms/Event/'.$formSlug.'/items'), [
                'withDetails' => 'true',
                'pageSize' => 100,
                'pageIndex' => $page,
            ]), self::READ_TIMEOUT);

            // A maintenance page answered with 200 must not pass for an empty list.
            if (! is_array($response['data'] ?? null)) {
                throw new HelloAssoUnavailable("Unexpected answer of HelloAsso for the items of [{$formSlug}].");
            }

            $items = [...$items, ...array_values($response['data'])];
            $totalPages = (int) ($response['pagination']['totalPages'] ?? 1);
            $page++;
        } while ($page <= $totalPages);

        return $items;
    }

    public function baseUrl(): string
    {
        return config('trees.helloasso.sandbox') ? 'https://api.helloasso-sandbox.com' : 'https://api.helloasso.com';
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     *
     * @throws HelloAssoUnavailable
     */
    private function send(callable $call, int $timeout = 15): array
    {
        try {
            return (array) $call(Http::withToken($this->token())->acceptJson()->timeout($timeout))->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            throw new HelloAssoUnavailable($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @throws HelloAssoUnavailable
     */
    private function token(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        try {
            $response = Http::asForm()->acceptJson()->timeout(15)->post($this->baseUrl().'/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('trees.helloasso.client_id'),
                'client_secret' => config('trees.helloasso.client_secret'),
            ])->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new HelloAssoUnavailable($exception->getMessage(), previous: $exception);
        }

        $token = (string) $response['access_token'];
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, (int) $response['expires_in'] - 60));

        return $token;
    }

    private function organizationUrl(string $path): string
    {
        return $this->baseUrl().'/v5/organizations/'.config('trees.helloasso.organization_slug').'/'.$path;
    }
}
