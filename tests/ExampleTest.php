<?php

namespace KDuma\SoapServer\Tests;

use Orchestra\Testbench\TestCase;
use Kduma\SoapServer\LaravelSoapServerServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelSoapServerServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
