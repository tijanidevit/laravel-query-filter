<?php
namespace Tijanidevit\QueryFilter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tijanidevit\QueryFilter\Providers\FilterProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FilterProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }
}
