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
    |--------------------------------------------------------------------------
    | SMS providers
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'nextsms' => [
            'base_url' => env('PRMS_SMS_BASE_URL', 'https://app.nextsms.co.tz'),
            'endpoint' => env('PRMS_SMS_ENDPOINT', '/api/sms/v1/text/single'),
            'test_endpoint' => env('PRMS_SMS_TEST_ENDPOINT', '/api/sms/v1/text/single'),
            'test_mode' => env('PRMS_SMS_TEST_MODE', false),
            /** Full URL override; legacy api.nextsms.com URLs are auto-corrected. */
            'url' => env('PRMS_SMS_HTTP_URL'),
            /** NextSMS app API expects Basic auth (base64 username:password). */
            'auth' => env('PRMS_SMS_AUTH', 'basic'),
            'token' => env('PRMS_SMS_HTTP_TOKEN'),
            'username' => env('PRMS_SMS_USERNAME'),
            'password' => env('PRMS_SMS_PASSWORD'),
            /** Optional pre-encoded Basic key from the NextSMS dashboard (api_key). */
            'basic_auth' => env('PRMS_SMS_BASIC_AUTH'),
            'sender_id' => env('PRMS_SMS_SENDER_ID', 'PRMSMoCU'),
            'callback_url' => env('PRMS_SMS_CALLBACK_URL'),
            /** auto | from_to_text | sender_mobile_message */
            'payload_format' => env('PRMS_SMS_PAYLOAD_FORMAT', 'auto'),
            'use_local_mobile_format' => env('PRMS_SMS_USE_LOCAL_MOBILE', false),
            'timeout' => (int) env('PRMS_SMS_TIMEOUT', 15),
        ],

        'messaging_service' => [
            'base_url' => env('PRMS_SMS_MESSAGING_BASE_URL', 'https://messaging-service.co.tz'),
            'endpoint' => env('PRMS_SMS_MESSAGING_ENDPOINT', '/api/sms/v2/text/single'),
            'test_endpoint' => env('PRMS_SMS_MESSAGING_TEST_ENDPOINT', '/api/sms/v2/test/text/single'),
            'test_mode' => env('PRMS_SMS_MESSAGING_TEST_MODE', false),
            'url' => env('PRMS_SMS_MESSAGING_URL'),
            'token' => env('PRMS_SMS_MESSAGING_TOKEN'),
            'sender_id' => env('PRMS_SMS_MESSAGING_SENDER_ID', 'MoCU-PRMS'),
            'flash' => (int) env('PRMS_SMS_MESSAGING_FLASH', 0),
        ],
    ],

];
