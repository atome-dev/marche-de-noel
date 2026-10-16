<?php

namespace App\TreeOrders\HelloAsso;

/**
 * HelloAsso shop of the trees, used when the site has no access to the
 * HelloAsso API: the family pays in the shop, then a manager records the
 * payment seen in the HelloAsso back office.
 */
class HelloAssoShop
{
    public function __construct(private HelloAssoClient $client) {}

    /**
     * The API, when configured, is preferred: its payments are confirmed
     * automatically. The shop is then offered beside it only to compare
     * both (trees.helloasso.shop_with_api).
     */
    public function url(): ?string
    {
        $url = config('trees.helloasso.shop_url');

        if (blank($url) || ($this->client->isConfigured() && ! config('trees.helloasso.shop_with_api'))) {
            return null;
        }

        return rtrim((string) $url, '/');
    }

    /**
     * The shop is the only online payment: the API is not configured.
     */
    public function replacesApi(): bool
    {
        return $this->url() !== null && ! $this->client->isConfigured();
    }

    /**
     * Shop widget embedded in the payment page of an order, when the shop is
     * displayed in an iframe rather than opened with a link.
     */
    public function widgetUrl(): ?string
    {
        $url = $this->url();

        return $url !== null && config('trees.helloasso.shop_display') === 'iframe' ? $url.'/widget' : null;
    }
}
