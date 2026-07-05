<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KaiAdmin Lite asset path
    |--------------------------------------------------------------------------
    |
    | Path under the public directory (no leading slash). Copied from the
    | KaiAdmin Lite 1.2.0 distribution into public/vendor/...
    |
    */
    'kaiadmin_assets' => env('PRMS_KAIADMIN_ASSETS', 'vendor/prms-mocu/assets'),

    /*
    |--------------------------------------------------------------------------
    | Help desk (sign-in page and support links)
    |--------------------------------------------------------------------------
    */
    'help_desk' => [
        'label' => env('PRMS_HELP_DESK_LABEL', 'ICT Help Desk'),
        'email' => env('PRMS_HELP_DESK_EMAIL', 'icthelpdesk@mocu.ac.tz'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Site copyright (footers, print views, exports)
    |--------------------------------------------------------------------------
    */
    'copyright' => [
        'university' => env('PRMS_COPYRIGHT_UNIVERSITY', 'Moshi Co-operative University'),
        'system_name' => env('PRMS_COPYRIGHT_SYSTEM', 'project and research management system'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Final-year workflow defaults
    |--------------------------------------------------------------------------
    |
    | Programme and department rules override these values. Used when a
    | programme has no explicit final_year / output_type configured.
    |
    */
    'workflow' => [
        'default_academic_level' => env('PRMS_DEFAULT_ACADEMIC_LEVEL', 'bachelor'),
        'default_workflow_type' => env('PRMS_DEFAULT_WORKFLOW_TYPE', 'standard'),
        'default_final_year' => [
            'diploma' => (int) env('PRMS_FINAL_YEAR_DIPLOMA', 2),
            'bachelor' => (int) env('PRMS_FINAL_YEAR_BACHELOR', 3),
            'masters' => (int) env('PRMS_FINAL_YEAR_MASTERS', 2),
            'phd' => (int) env('PRMS_FINAL_YEAR_PHD', 3),
        ],
        /** Only this diploma programme code may conduct a PRMS project. */
        'project_diploma_programme_code' => env('PRMS_PROJECT_DIPLOMA_CODE', 'DBICT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS gateway (group formation, supervisor assignment, workflow alerts)
    |--------------------------------------------------------------------------
    |
    | When disabled, SMS bodies are written to the application log only.
    | Set driver to "http" and PRMS_SMS_HTTP_URL when a gateway is available.
    |
    */
    'sms' => [
        'enabled' => env('PRMS_SMS_ENABLED', false),
        'driver' => env('PRMS_SMS_DRIVER', 'log'),
        'http_url' => env('PRMS_SMS_HTTP_URL'),
        'http_token' => env('PRMS_SMS_HTTP_TOKEN'),
        'sender_id' => env('PRMS_SMS_SENDER_ID', 'MoCU-PRMS'),
    ],
];
