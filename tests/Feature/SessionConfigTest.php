<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionConfigTest extends TestCase
{
    public function test_default_session_driver_needs_no_sessions_table(): void
    {
        $this->assertSame('file', (string) preg_replace('/.*env\(\'SESSION_DRIVER\', \'(\w+)\'\).*/s', '$1', file_get_contents(config_path('session.php')) ?: ''), 'config default');
    }
}
