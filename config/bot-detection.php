<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User-agent substrings (case-insensitive)
    |--------------------------------------------------------------------------
    |
    | When any of these appear in the lowercased User-Agent, the request is
    | treated as likely automated for redirect/resolver analytics split.
    |
    */
    'ua_substrings' => [
        'googlebot',
        'mediapartners-google',
        'bingbot',
        'baiduspider',
        'yandexbot',
        'duckduckbot',
        'facebookexternalhit',
        'linkedinbot',
        'twitterbot',
        'embedly',
        'whatsapp',
        'telegrambot',
        'discordbot',
        'slackbot',
        'crawl',
        'spider',
        'slurp',
        'curl/',
        'wget/',
        'python-requests',
        'go-http-client',
        'java/',
        'httpclient',
        'okhttp',
        'axios/',
        'node-fetch',
        'libwww-perl',
        'http.rb',
        'postman',
        'insomnia',
        'headlesschrome',
        'puppeteer',
        'playwright',
        'phantomjs',
        'selenium',
        'lighthouse',
        'preview',
        'bot',
    ],

    'redirect' => [

        'max_bot_attempts_per_window' => (int) env('BOT_DETECTION_REDIRECT_MAX_PER_WINDOW', 40),

        'decay_seconds' => (int) env('BOT_DETECTION_REDIRECT_DECAY_SECONDS', 60),
    ],

    'resolver' => [

        'max_bot_attempts_per_window' => (int) env('BOT_DETECTION_RESOLVER_MAX_PER_WINDOW', 20),

        'decay_seconds' => (int) env('BOT_DETECTION_RESOLVER_DECAY_SECONDS', 60),
    ],
];
