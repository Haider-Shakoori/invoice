<?php

return [
    'trial_days' => (int) env('INVOICE_TRIAL_DAYS', 7),
    'pricing' => [
        'activation_setup_afn' => (int) env('INVOICE_SETUP_PRICE_AFN', 2000),
        'first_year_afn' => (int) env('INVOICE_FIRST_YEAR_PRICE_AFN', 3000),
        'renewal_afn' => (int) env('INVOICE_RENEWAL_PRICE_AFN', 3000),
    ],
    'commercial' => [
        'payment_due_days' => (int) env('INVOICE_PAYMENT_DUE_DAYS', 7),
        'grace_days' => (int) env('INVOICE_GRACE_DAYS', 0),
    ],
    'pdf' => [
        'chromium_binary' => env('PDF_CHROMIUM_BINARY'),
        'timeout_seconds' => (int) env('PDF_TIMEOUT_SECONDS', 30),
        'font_family' => env('PDF_FONT_FAMILY', 'Inter, DejaVu Sans, Arial, sans-serif'),
        'rtl_font_family' => env('PDF_RTL_FONT_FAMILY', 'Noto Naskh Arabic, Noto Sans Arabic, DejaVu Sans, Arial, sans-serif'),
    ],
    'locales' => ['en', 'fa', 'ps'],
    'default_locale' => env('APP_LOCALE', 'en'),
    'reserved_subdomains' => [
        'www', 'admin', 'app', 'api', 'mail', 'email', 'support', 'help', 'status', 'billing',
        'account', 'accounts', 'login', 'register', 'signup', 'static', 'assets', 'cdn', 'files',
        'docs', 'documentation', 'demo', 'test', 'staging', 'dev', 'platform', 'invoice',
    ],
];
