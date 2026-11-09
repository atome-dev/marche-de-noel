<?php

use App\Support\UploadLimit;
use Tests\TestCase;

uses(TestCase::class);

function uploadLimit(int $serverBytes, int $configuredMegabytes = 20): UploadLimit
{
    config(['marche.documents.max_size_mb' => $configuredMegabytes]);

    return Mockery::mock(UploadLimit::class)->makePartial()
        ->shouldReceive('serverBytes')->andReturn($serverBytes)
        ->getMock();
}

test('the effective limit is the configured one when the server allows more', function () {
    $limit = uploadLimit(64 * 1024 * 1024);

    expect($limit->megabytes())->toBe(20)
        ->and($limit->isReducedByServer())->toBeFalse();
});

test('the effective limit is the server one when PHP allows less', function () {
    $limit = uploadLimit(2 * 1024 * 1024);

    expect($limit->megabytes())->toBe(2)
        ->and($limit->serverMegabytes())->toBe(2)
        ->and($limit->isReducedByServer())->toBeTrue();
});

test('a server limit under one megabyte is still announced as at least one', function () {
    expect(uploadLimit(512 * 1024)->megabytes())->toBe(1);
});

test('the server limit is read from the PHP settings', function () {
    expect((new UploadLimit)->serverBytes())->toBeGreaterThan(0);
});
