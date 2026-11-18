<?php

namespace Tests\Feature;

use Tests\TestCase;
use Inertia\Testing\AssertableInertia;

class SmokeTest extends TestCase
{
    /**
     * The home page renders the dashboard component.
     */
    public function test_home_renders_dashboard(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard/Index'));
    }
}
