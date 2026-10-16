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
     * automatically.
     */
    public function url(): ?string
    {
        $url = config('trees.helloasso.shop_url');

        return filled($url) && ! $this->client->isConfigured() ? (string) $url : null;
    }
}
