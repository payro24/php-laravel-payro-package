<?php

namespace Payro\Payment\Provider;

use Payro\Payment\PaymentManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'PayroPayment');

        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../config/payment.php' => config_path('payment.php'),
            ],
            'config'
        );

        /**
         * Views that needs to be modified by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../resources/views' => resource_path('views/vendor/PayroPayment')
            ],
            'views'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Bind to service container.
         */
        $this->app->bind('Payro-payment', function () {
            return new PaymentManager(config('payment'));
        });
    }
}
