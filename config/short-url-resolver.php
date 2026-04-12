<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum total resolution steps
    |--------------------------------------------------------------------------
    |
    | Each internal short-link hop or each outbound HTTP redirect counts as one
    | step. Prevents infinite chains (e.g. A → B → A).
    |
    */
    'max_expand_hops' => (int) env('SHORT_URL_RESOLVER_MAX_EXPAND_HOPS', 25),

    /*
    |--------------------------------------------------------------------------
    | Per-request timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout_seconds' => (int) env('SHORT_URL_RESOLVER_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Enforce DNS resolves to public IPs only
    |--------------------------------------------------------------------------
    |
    | When true (production default), every hostname is resolved and all A/AAAA
    | answers must be public addresses. Disable in PHPUnit to allow Http::fake()
    | without outbound DNS.
    |
    */
    'enforce_dns_public_ips' => filter_var(
        env('SHORT_URL_RESOLVER_ENFORCE_DNS', true),
        FILTER_VALIDATE_BOOLEAN,
    ),

];
