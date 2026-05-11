<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */
    'defaults' => [
        'guard' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the GenericUser provider.
    |
    | Supported Drivers: "session", "jwt"
    |
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    */
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
            // 'callback' => function($id) { ... }
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the JWT driver.
    |
    */
    'jwt' => [
        'secret' => 'your-secret-key-change-me',
        'algo' => 'HS256',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA Configuration
    |--------------------------------------------------------------------------
    */
    'captcha' => [
        'driver' => 'recaptcha',
        'drivers' => [
            'recaptcha' => [
                'site_key' => 'YOUR_RECAPTCHA_SITE_KEY',
                'secret_key' => 'YOUR_RECAPTCHA_SECRET_KEY',
            ],
            'hcaptcha' => [
                'site_key' => 'YOUR_HCAPTCHA_SITE_KEY',
                'secret_key' => 'YOUR_HCAPTCHA_SECRET_KEY',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MFA Configuration
    |--------------------------------------------------------------------------
    */
    'mfa' => [
        'enabled' => true,
        'issuer' => 'DarkAuth System',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Define a callback to handle security events logging.
    |
    */
    'audit' => [
        'enabled' => true,
        // 'callback' => function($data) { ... },
    ],
];
