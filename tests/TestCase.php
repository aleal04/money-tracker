<?php

namespace Tests;

use Tests\Concerns\CreatesSchemaTrait;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesSchemaTrait;

    /**
     * Boot the application without the Vite manifest, so the tests do not need a frontend build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
