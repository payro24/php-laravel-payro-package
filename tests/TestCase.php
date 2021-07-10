<?php

namespace Shetabit\Payment\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shetabit\Payment\Tests\Mocks\Drivers\BarDriver;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return ['Payro\Payment\Provider\PaymentServiceProvider'];
    }
    protected function getEnvironmentSetUp($app)
    {
        $settings = require __DIR__.'/../src/Config/payment.php';
        $settings['drivers']['bar'] = ['key' => 'foo'];
        $settings['map']['bar'] = BarDriver::class;

        $app['config']->set('payment', $settings);
    }
}
