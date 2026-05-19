<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // phpspreadsheet's Xlsx writer (via ZipStream) pre-allocates 16MB
        // chunks per fixture; the default 128M PHP limit is exhausted after
        // a few sequential writes in the importer tests. 256M is generous
        // headroom for the whole suite.
        if ((int) ini_get('memory_limit') < 256 * 1024 * 1024 && ini_get('memory_limit') !== '-1') {
            ini_set('memory_limit', '256M');
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
