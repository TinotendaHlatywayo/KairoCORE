<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            // MAIL_SCHEME wins when set and must be 'smtp' or 'smtps'
            // (Symfony rejects anything else). MAIL_ENCRYPTION is the
            // Laravel-documented name: 'ssl' means implicit TLS (smtps, the
            // usual 465 setup); 'tls' means opportunistic STARTTLS on a
            // plain connection, which Laravel applies automatically, so it
            // maps to no scheme and lets the port decide (465 => smtps).
            'scheme' => env('MAIL_SCHEME')
                ?: (strtolower((string) env('MAIL_ENCRYPTION')) === 'ssl' ? 'smtps' : null),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
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
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@kairocore.co.zw'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Views
    |--------------------------------------------------------------------------
    |
    | Published, restyled copies of Laravel's stock mail templates live here
    | and take precedence over the framework originals (the first path wins).
    |
    */

    'markdown' => [
        'theme' => 'default',
        'paths' => [resource_path('views/vendor/mail')],
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Email Identity & Role Addresses
    |--------------------------------------------------------------------------
    |
    | This is the single platform-level sending account (used by the platform
    | operator, e.g. for security alerts or billing). It is intentionally kept
    | separate from tenant email configurations. Tenant configurations must NOT
    | reuse this identity as their sender, otherwise emails would be flagged as
    | sent on behalf of the platform account.
    |
    | The three role addresses below are the platform's public mailboxes:
    |   noreply  — From: for all automated/system mail (activation, receipts,
    |              billing notices, registration alerts).
    |   support  — human-replyable inbox: contact form, tenant messages,
    |              billing/subscription operations; reply-to on system mail.
    |   sales    — new-school registrations and commercial enquiries.
    |
    */

    'platform' => [
        'address' => env('MAIL_PLATFORM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@kairocore.co.zw')),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
        'noreply' => env('MAIL_NOREPLY_ADDRESS', 'noreply@kairocore.co.zw'),
        'support' => env('MAIL_SUPPORT_ADDRESS', 'support@kairocore.co.zw'),
        'sales' => env('MAIL_SALES_ADDRESS', 'sales@kairocore.co.zw'),
    ],

];
