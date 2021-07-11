<?php

namespace Payro\Payment\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Sms
 *
 * @package Payro\Payment\Facade
 * @see \Payro\Payment\PaymentManager
 */
class Payment extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'Payro-payment';
    }
}
