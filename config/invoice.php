<?php

return [
    'trial_days' => (int) env('INVOICE_TRIAL_DAYS', 7),
    'pricing' => [
        'activation_setup_afn' => (int) env('INVOICE_SETUP_PRICE_AFN', 2000),
        'first_year_afn' => (int) env('INVOICE_FIRST_YEAR_PRICE_AFN', 3000),
        'renewal_afn' => (int) env('INVOICE_RENEWAL_PRICE_AFN', 3000),
    ],
    'locales' => ['en', 'fa', 'ps'],
    'default_locale' => env('APP_LOCALE', 'en'),
    'reserved_subdomains' => [
        'www', 'admin', 'app', 'api', 'mail', 'email', 'support', 'help', 'status', 'billing',
        'account', 'accounts', 'login', 'register', 'signup', 'static', 'assets', 'cdn', 'files',
        'docs', 'documentation', 'demo', 'test', 'staging', 'dev', 'platform', 'invoice',
    ],
];
