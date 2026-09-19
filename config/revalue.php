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

];
