<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the application without the Vite manifest, so the tests do not need a frontend build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
