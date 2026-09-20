<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Development Super Admin
    |--------------------------------------------------------------------------
    |
    | Credentials used by SuperAdminSeeder to create the local development
    | super admin account. The defaults below are deliberately throwaway
    | development values and MUST be overridden (and rotated) before this
    | project is deployed anywhere real. Never commit real credentials.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'ReValue Super Admin (DEV)'),
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@revalue.test'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

    'fees' => [
        'delivery' => (int) env('REVALUE_DELIVERY_FEE', 600),
        'service' => (int) env('REVALUE_SERVICE_FEE', 300),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL', 'https://karanja.ninja/api/paystack/callback'),
        'currency' => env('PAYSTACK_CURRENCY', 'KES'),

        // Used only when a demo account uses a reserved domain such as
        // @revalue.test, which Paystack rejects as an invalid email.
        'billing_email' => env('PAYSTACK_BILLING_EMAIL', 'payments@revalue.co.ke'),
    ],

];
