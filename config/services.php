<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Google Maps (Maps/Logistics, Person 4). The JavaScript key is used by the
    | browser to render maps and draw routes. Leave blank in local/dev and the
    | UI degrades gracefully to a coordinate list instead of a live map.
    */
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'default_center' => [
            'lat' => (float) env('MAP_DEFAULT_LAT', -1.286389),
            'lng' => (float) env('MAP_DEFAULT_LNG', 36.817223),
        ],
    ],

];
