<?php

return [
    /*----------------------------------------*
     * Basic
     *----------------------------------------*/

    "enable"    => env("LOG_ACCESS_ENABLE", false),
    "directory" => env("LOG_ACCESS_DIRECTORY", "access"),

    "ignore_uri" => [],

    /*----------------------------------------*
     * Logging
     *----------------------------------------*/

    "execution_time"    => env("LOG_ACCESS_EXECUTION_TIME", false),
    "memory_peak_usage" => env("LOG_ACCESS_MEMORY_PEAK_USAGE", false),

    "request_url"         => env("LOG_ACCESS_REQUEST_URL", false),
    "request_http_method" => env("LOG_ACCESS_REQUEST_HTTP_METHOD", false),
    "request_user_agent"  => env("LOG_ACCESS_REQUEST_USER_AGENT", false),
    "request_ip_address"  => env("LOG_ACCESS_REQUEST_IP_ADDRESS", false),
    "request_body"        => env("LOG_ACCESS_REQUEST_BODY", false),

    "response_status"      => env("LOG_ACCESS_RESPONSE_STATUS", false),
    "response_status_text" => env("LOG_ACCESS_RESPONSE_STATUS_TEXT", false),

    /*----------------------------------------*
     * Masking
     *----------------------------------------*/

    "masking_text"         => env("LOG_ACCESS_MASKING_TEXT", "********"),
    "masking_parameters" => [
        "password",
        "password_confirmation",
        "current_password",
        "new_password",
        "new_password_confirmation",
    ],
];
