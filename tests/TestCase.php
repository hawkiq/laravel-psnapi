<?php

namespace Hawkiq\LaravelPsnApi\Tests;

use Hawkiq\LaravelPsnApi\Providers\PsnApiServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PsnApiServiceProvider::class,
        ];
    }
}
