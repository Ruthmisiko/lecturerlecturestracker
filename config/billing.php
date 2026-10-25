<?php

// Business details printed on invoices issued from the System Owner console.
return [
    'name'     => env('BILLING_NAME', 'Nawema'),
    'email'    => env('BILLING_EMAIL', 'nawema.africa@gmail.com'),
    'website'  => env('BILLING_WEBSITE', 'nawema.co.ke'),
    'phone'    => env('BILLING_PHONE', ''),
    'address'  => env('BILLING_ADDRESS', ''),
    'payment'  => env('BILLING_PAYMENT_DETAILS', ''), // e.g. "M-Pesa Paybill 123456, Acc: invoice number"
    'currency' => env('BILLING_CURRENCY', 'KES'),
];
