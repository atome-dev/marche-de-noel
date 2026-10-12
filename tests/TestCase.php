<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    /**
     * No test may touch the real storage: the purge, for instance, deletes
     * the documents and snapshots directories.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        // Nor the real snapshot log.
        config(['logging.channels.snapshots' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
