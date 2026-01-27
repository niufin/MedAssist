<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $env = (string) config('app.env');
        if ($env !== 'testing') {
            return;
        }

        $default = (string) config('database.default');
        if ($default !== 'sqlite') {
            throw new \RuntimeException("Unsafe test DB configuration: database.default={$default}. Tests must run on sqlite to protect MySQL data.");
        }

        $db = (string) config('database.connections.sqlite.database');
        $dbNorm = str_replace('\\', '/', $db);
        if ($db !== ':memory:' && !str_ends_with($dbNorm, '/database.sqlite')) {
            throw new \RuntimeException("Unsafe sqlite test DB configuration: sqlite.database={$db}. Use :memory: or database/database.sqlite.");
        }
    }
}
