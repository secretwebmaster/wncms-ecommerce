<?php

namespace Secretwebmaster\WncmsEcommerce\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }
}
