<?php

use App\Exceptions\HelloAssoUnavailable;
use App\Models\TreeOrder;
use App\Models\User;
use App\TreeOrders\HelloAsso\HelloAssoClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['trees.helloasso' => [
        'client_id' => 'id',
        'client_secret' => 'secret',
        'organization_slug' => 'ecole-steiner',
        'sandbox' => true,
    ]]);
    Cache::flush();

    $this->client = app(HelloAssoClient::class);
});

function fakeHelloAsso(array $responses = []): void
{
    Http::fake([
        'api.helloasso-sandbox.com/oauth2/token' => Http::response(['access_token' => 'token-1', 'expires_in' => 1800]),
        ...$responses,
    ]);
}

test('it is configured only with all the credentials', function () {
    expect($this->client->isConfigured())->toBeTrue();

    config(['trees.helloasso.client_secret' => null]);

    expect($this->client->isConfigured())->toBeFalse();
});

test('a checkout intent is created for the exact amount, with the order in its metadata', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/organizations/ecole-steiner/checkout-intents' => Http::response(['id' => 987, 'redirectUrl' => 'https://pay.example/987']),
    ]);

    $user = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'email' => 'camille@example.com']);
    $order = TreeOrder::factory()->for($user)->withLine('moyen', 2, 3500)->create(['customer_email' => 'camille@example.com']);

    $checkout = $this->client->createCheckout($order, 'https://site/retour', 'https://site/abandon', 'https://site/erreur');

    expect($checkout)->toBe(['id' => 987, 'redirectUrl' => 'https://pay.example/987']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.helloasso-sandbox.com/oauth2/token'
        && $request['grant_type'] === 'client_credentials'
        && $request['client_id'] === 'id');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/checkout-intents')
        && $request->hasHeader('Authorization', 'Bearer token-1')
        && $request['totalAmount'] === 7000
        && $request['initialAmount'] === 7000
        && $request['containsDonation'] === false
        && $request['returnUrl'] === 'https://site/retour'
        && $request['backUrl'] === 'https://site/abandon'
        && $request['errorUrl'] === 'https://site/erreur'
        && $request['payer'] === ['email' => 'camille@example.com', 'firstName' => 'Camille', 'lastName' => 'Martin']
        && $request['metadata'] === ['tree_order_id' => $order->id, 'number' => $order->number]);
});

test('a name refused by HelloAsso is left to the payer', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/organizations/ecole-steiner/checkout-intents' => Http::response(['id' => 987, 'redirectUrl' => 'https://pay.example/987']),
    ]);

    $user = User::factory()->create(['first_name' => 'Zoé2', 'last_name' => 'Martin', 'email' => 'zoe@example.com']);
    $order = TreeOrder::factory()->for($user)->withLine('moyen', 1, 3500)->create(['customer_email' => 'zoe@example.com']);

    $this->client->createCheckout($order, 'https://site/retour', 'https://site/abandon', 'https://site/erreur');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/checkout-intents')
        && $request['payer'] === ['email' => 'zoe@example.com']);
});

test('the token is requested once for several calls', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/organizations/ecole-steiner/checkout-intents/*' => Http::response(['id' => 987]),
    ]);

    $this->client->fetchCheckout(987);
    $this->client->fetchCheckout(987);

    Http::assertSentCount(3);
});

test('production uses the production api', function () {
    config(['trees.helloasso.sandbox' => false]);

    Http::fake([
        'api.helloasso.com/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        'api.helloasso.com/v5/organizations/ecole-steiner/checkout-intents/5' => Http::response(['id' => 5]),
    ]);

    expect($this->client->fetchCheckout(5))->toBe(['id' => 5]);
});

test('http errors become HelloAssoUnavailable', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/*' => Http::response(['message' => 'boom'], 500),
    ]);

    $this->client->fetchCheckout(5);
})->throws(HelloAssoUnavailable::class);

test('a refused token becomes HelloAssoUnavailable', function () {
    Http::fake(['api.helloasso-sandbox.com/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

    $this->client->fetchCheckout(5);
})->throws(HelloAssoUnavailable::class);
