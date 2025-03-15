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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'endpoint' => env('GOOGLE_TRANSLATE_ENDPOINT', 'https://translation.googleapis.com/language/translate/v2'),
    ],

    'cloudfront' => [
        'distribution_id' => env('CLOUDFRONT_DISTRIBUTION_ID'),
        'key' => env('CLOUDFRONT_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('CLOUDFRONT_SECRET', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('CLOUDFRONT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],

];
