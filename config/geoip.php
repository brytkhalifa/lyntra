<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GeoIP (MaxMind GeoLite2 / GeoIP2 City) lookups for link clicks
    |--------------------------------------------------------------------------
    |
    | When enabled, the redirect handler resolves the client IP against a local
    | MMDB file (no raw IP is stored — only country/region/city and ip_hash).
    |
    | Download GeoLite2-City from https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
    | and set GEOIP_DATABASE_PATH, or place the file at storage_path('geo/GeoLite2-City.mmdb').
    |
    */
    'enabled' => filter_var(env('GEOIP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'database' => env('GEOIP_DATABASE_PATH', storage_path('geo/GeoLite2-City.mmdb')),

];
