<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following gateway to use.
    | You can switch to a different driver at runtime.
    |
    */
    'default' => 'payro24',

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | These are the list of drivers to use for this package.
    | You can change the name. Then you'll have to change
    | it in the map array too.
    |
    */
    'drivers' => [
        'payro24' => [
            'apiPurchaseUrl' => 'https://api.payro24.ir/v1.0/payment',
            'apiPaymentUrl' => 'https://payro24.ir/',
            'apiSandboxPaymentUrl' => 'https://payro24.ir/p/ws-sandbox/',
            'apiVerificationUrl' => 'https://api.payro24.ir/v1.0/payment/verify',
            'merchantId' => '',
            'callbackUrl' => 'http://yoursite.com/path/to',
            'description' => 'payment in '.config('app.name'),
            'sandbox' => false, // set it to true for test environments
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Maps
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to extend
    | Payro\Payment\Abstracts\Driver in your driver.
    |
    */
    'map' => [
        'payro24' => \Payro\Payment\Drivers\payro24\payro24::class,
    ]
];
