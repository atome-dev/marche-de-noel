<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A ticket as returned by the HelloAsso items endpoint (withDetails=true).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function helloAssoTicket(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 110885,
        'name' => 'Adulte',
        'tierId' => 24279,
        'amount' => 1000,
        'state' => 'Processed',
        'type' => 'Registration',
        'user' => ['firstName' => 'Camille', 'lastName' => 'Martin'],
        'payer' => ['firstName' => 'Camille', 'lastName' => 'Martin', 'email' => 'camille@example.org'],
        'order' => ['id' => 100115, 'date' => '2026-10-16T23:32:36+02:00', 'formSlug' => 'orchestre-des-eleves', 'formType' => 'Event'],
        'payments' => [['id' => 72031, 'state' => 'Authorized', 'refundOperations' => []]],
        'ticketUrl' => 'https://www.helloasso-sandbox.com/ticket?ticketId=110885',
        'qrCode' => 'MTEwODg1',
        'isScanned' => false,
    ], $overrides);
}
