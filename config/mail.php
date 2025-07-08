<?php

return [

'default' => env('MAIL_MAILER', 'log'),

'mailers' => [

    'smtp' => [
        'transport' => 'smtp',
        'url' => env('MAIL_URL'),
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => env('MAIL_PORT', 2525),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'timeout' => null,
        'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    ],

    'ses' => [
        'transport' => 'ses',
    ],

    'postmark' => [
        'transport' => 'postmark',
    ],

    'resend' => [
        'transport' => 'resend',
    ],

    'sendmail' => [
        'transport' => 'sendmail',
        'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
    ],

    'log' => [
        'transport' => 'log',
        'channel' => env('MAIL_LOG_CHANNEL'),
    ],

    'array' => [
        'transport' => 'array',
    ],

    'failover' => [
        'transport' => 'failover',
        'mailers' => [
            'smtp',
            'log',
        ],
    ],

    'roundrobin' => [
        'transport' => 'roundrobin',
        'mailers' => [
            'ses',
            'postmark',
        ],
    ],

    // Additional SMTP mailer 1
    'smtp1' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST_SMTP1', 'smtp1.example.com'),
        'port' => env('MAIL_PORT_SMTP1', 587),
        'encryption' => env('MAIL_ENCRYPTION_SMTP1', 'tls'),
        'username' => env('MAIL_USERNAME_SMTP1'),
        'password' => env('MAIL_PASSWORD_SMTP1'),
        'timeout' => null,

    ],

    // Additional SMTP mailer 2
    'smtp2' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST_SMTP2', 'smtp2.example.com'),
        'port' => env('MAIL_PORT_SMTP2', 465),
        'encryption' => env('MAIL_ENCRYPTION_SMTP2', 'ssl'),
        'username' => env('MAIL_USERNAME_SMTP2'),
        'password' => env('MAIL_PASSWORD_SMTP2'),
        'timeout' => null,

    ],

],

'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Example'),
],

];
