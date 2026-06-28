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
    | Initial administrator (production seed)
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder during `php artisan db:seed`. Set a strong
    | PRMS_ADMIN_PASSWORD before deploying; the account is created once and
    | can be updated safely on re-seed via updateOrCreate.
    |
    */
    'admin' => [
        'name' => env('PRMS_ADMIN_NAME', 'System Administrator'),
        'email' => env('PRMS_ADMIN_EMAIL'),
        'password' => env('PRMS_ADMIN_PASSWORD'),
        'login_id' => env('PRMS_ADMIN_LOGIN_ID', 'MoCU/ADMIN/001'),
        'staff_id' => env('PRMS_ADMIN_STAFF_ID', env('PRMS_ADMIN_LOGIN_ID', 'MoCU/ADMIN/001')),
        'must_change_password' => env('PRMS_ADMIN_MUST_CHANGE_PASSWORD', true),
    ],

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
    ],
];
