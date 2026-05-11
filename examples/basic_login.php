<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkauth\Auth\AuthManager;
use Darkauth\Models\GenericUser;

// 1. Setup configuration
$config = [
    'defaults' => [
        'guard' => 'web',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'callback' => function($id) {
                // Mock database lookup
                $users = [
                    1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                    2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
                ];
                
                return isset($users[$id]) ? new GenericUser($users[$id]) : null;
            }
        ],
    ],
];

// 2. Initialize Manager
$auth = new AuthManager($config);

// 3. Simulate Login
$john = new GenericUser(['id' => 1, 'name' => 'John Doe']);
$auth->guard('web')->login($john);

// 4. Check Authentication
if ($auth->check()) {
    echo "Current User ID: " . $auth->id() . PHP_EOL;
    echo "Current User Name: " . $auth->user()->name . PHP_EOL;
}

// 5. Logout
$auth->logout();

if ($auth->guest()) {
    echo "User has been logged out." . PHP_EOL;
}
