<?php

use App\Models\Admin;
use App\Models\Voter;

return [

    'defaults' => [
        'guard' => 'voter',
        'passwords' => 'voters',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'voter' => [
            'driver' => 'session',
            'provider' => 'voters',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],

        'voters' => [
            'driver' => 'eloquent',
            'model' => Voter::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'voters' => [
            'provider' => 'voters',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
