<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->logPath = storage_path('framework/testing/helloasso-calls.log');
    File::delete($this->logPath);

    config(['logging.channels.helloasso' => ['driver' => 'single', 'path' => $this->logPath, 'level' => 'info']]);
});

afterEach(function () {
    File::delete($this->logPath);
});

test('a POST call to /helloasso is logged with its content and the response status', function () {
    $this->postJson('/helloasso?source=test', ['eventType' => 'Order', 'data' => ['id' => 777], 'metadata' => ['tree_order_id' => 999]])
        ->assertOk();

    expect(File::get($this->logPath))
        ->toContain('POST')
        ->toContain('"source":"test"')
        ->toContain('"eventType":"Order"')
        ->toContain('"tree_order_id":999')
        ->toContain('"status":200');
});

test('a GET call to /helloasso is accepted and logged with its parameters', function () {
    $this->get('/helloasso?checkoutIntentId=123&code=succeeded')->assertOk();

    expect(File::get($this->logPath))
        ->toContain('GET')
        ->toContain('"checkoutIntentId":"123"')
        ->toContain('"code":"succeeded"');
});

test('a body that is not JSON is logged as received', function () {
    $this->call('POST', '/helloasso', server: ['CONTENT_TYPE' => 'text/plain'], content: 'not json')->assertOk();

    expect(File::get($this->logPath))->toContain('not json');
});
