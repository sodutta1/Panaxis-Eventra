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

    /*
    |--------------------------------------------------------------------------
    | WhatsApp notifications (stub)
    |--------------------------------------------------------------------------
    | Drop a provider key (Twilio / Meta Cloud API) here to go live. Without a
    | key every WhatsApp notification is recorded in notification_logs with
    | status "stubbed_no_provider" and nothing is sent.
    */
    'whatsapp' => [
        'provider_key' => env('WHATSAPP_PROVIDER_KEY'),
        'sender' => env('WHATSAPP_SENDER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay (TEST MODE ONLY)
    |--------------------------------------------------------------------------
    | Placeholder test keys. No live charges: order creation and signature
    | verification are simulated while test_mode is true or keys are missing.
    */
    'razorpay' => [
        'key' => env('RAZORPAY_KEY', 'rzp_test_placeholder'),
        'secret' => env('RAZORPAY_SECRET', 'placeholder_secret'),
        'test_mode' => env('RAZORPAY_TEST_MODE', true),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
