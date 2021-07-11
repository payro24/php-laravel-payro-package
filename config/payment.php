<?php
return [
    'default' => 'payro24',

    'drivers' => [
        'payro24' => [
            'apiPurchaseUrl' => 'https://api.payro24.ir/v1.0/payment',
            'apiPaymentUrl' => 'https://payro24.ir/',
            'apiVerificationUrl' => 'https://api.payro24.ir/v1.0/payment/verify',
            'merchantId' => '',
            'callbackUrl' => 'http://yoursite.com/path/to',
            'description' => 'payment in ' . config('app.name'),
            'sandbox' => false, // set it to true for test environments
        ],
    ],

    'map' => [
        'payro24' => \Payro\Payment\Drivers\payro24\payro24::class,
    ]
];
