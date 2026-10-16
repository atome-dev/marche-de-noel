<?php

use App\TreeOrders\HelloAsso\HelloAssoPayerName;

test('names accepted by HelloAsso are sent', function (string $firstName, string $lastName) {
    expect(HelloAssoPayerName::fields($firstName, $lastName))->toBe(['firstName' => $firstName, 'lastName' => $lastName]);
})->with([
    ['Camille', 'Martin'],
    ['Noémie', 'Lefèvre'],
    ['Jean-François', "D'Arc"],
    ['Anne Sophie', 'Du Pont'],
    ['Ève', 'Bàù'],
    ['Zoë', 'Anaïs'],
    ['Jérôme', 'Müller'],
]);

test('curly apostrophes, typed by phone keyboards, are sent as straight ones', function () {
    expect(HelloAssoPayerName::fields('Anne-Lou', 'D’Arc'))->toBe(['firstName' => 'Anne-Lou', 'lastName' => "D'Arc"]);
});

test('surrounding spaces are removed', function () {
    expect(HelloAssoPayerName::fields('  Camille ', ' Martin  '))->toBe(['firstName' => 'Camille', 'lastName' => 'Martin']);
});

test('names refused by HelloAsso are not sent at all', function (string $firstName, string $lastName) {
    expect(HelloAssoPayerName::fields($firstName, $lastName))->toBe([]);
})->with([
    'digit' => ['Camille2', 'Martin'],
    'single character' => ['C', 'Martin'],
    'no vowel' => ['Camille', 'Prr'],
    'three repeated characters' => ['Annne', 'Martin'],
    'non latin alphabet' => ['Camille', 'Мартин'],
    'other special character' => ['Camille', 'Martin.'],
    'reserved value' => ['Test', 'Martin'],
    'reserved value, any case' => ['Camille', 'ANONYME'],
    'identical names' => ['Martin', 'martin'],
    'empty' => ['', 'Martin'],
    'too long' => [str_repeat('Ab', 128), 'Martin'],
]);
