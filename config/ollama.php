<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama (local LLM) — project / research similarity checks
    |--------------------------------------------------------------------------
    |
    | Requires Ollama running locally (default http://127.0.0.1:11434).
    | Pull the chat model: ollama pull mistral
    |
    */

    'enabled' => env('OLLAMA_ENABLED', true),

    'base_url' => rtrim(env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/'),

    'chat_model' => env('OLLAMA_CHAT_MODEL', 'mistral'),

    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),

    'similarity' => [
        /** Minimum local text overlap (0–100) before calling Mistral. */
        'text_prefilter_min' => (float) env('OLLAMA_SIMILARITY_TEXT_MIN', 12),

        /** Max other projects sent to Mistral per analysis run. */
        'max_candidates' => (int) env('OLLAMA_SIMILARITY_MAX_CANDIDATES', 8),

        /** Store pairs at or above this Mistral score (0–100). */
        'store_threshold' => (float) env('OLLAMA_SIMILARITY_STORE_THRESHOLD', 35),

        /** Notify supervisor/coordinator when score >= this value. */
        'alert_threshold' => (float) env('OLLAMA_SIMILARITY_ALERT_THRESHOLD', 65),

        'risk_levels' => [
            'low' => 39,
            'medium' => 64,
            'high' => 100,
        ],
    ],

];
