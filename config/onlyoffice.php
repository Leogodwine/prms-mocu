<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ONLYOFFICE Document Server
    |--------------------------------------------------------------------------
    |
    | DOCUMENT_SERVER_URL — URL the browser uses to load the ONLYOFFICE editor.
    | STORAGE_URL — URL the Document Server uses to fetch/save files from Laravel.
    | On Docker Desktop (Windows/Mac), set STORAGE_URL to http://host.docker.internal
    | so the container can reach your XAMPP/Laravel app.
    |
    */
    'document_server_url' => env('ONLYOFFICE_DOCUMENT_SERVER_URL', 'http://127.0.0.1:8080'),

    'storage_url' => env('ONLYOFFICE_STORAGE_URL', env('APP_URL', 'http://localhost')),

    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', ''),

    'jwt_enabled' => env('ONLYOFFICE_JWT_ENABLED', true),

    'storage_path' => 'word_documents',

    'max_upload_kb' => (int) env('ONLYOFFICE_MAX_UPLOAD_KB', 20480),
];
