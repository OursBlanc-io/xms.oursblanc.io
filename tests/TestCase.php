<?php

namespace OursBlanc\Xms\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use OursBlanc\Xms\XmsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            XmsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
