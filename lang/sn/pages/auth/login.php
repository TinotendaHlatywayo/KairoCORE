<?php

return [

    'title' => 'Kupinda',

    'heading' => 'Kupinda',

    'actions' => [

        'register' => [
            'before' => 'kana',
            'label' => 'nyorera kuti uve neakaundi',
        ],

        'request_password_reset' => [
            'label' => 'Wakanganwa password?',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Kero yeemail',
        ],

        'password' => [
            'label' => 'Password',
        ],

        'remember' => [
            'label' => 'Ndirangarire',
        ],

        'actions' => [

            'authenticate' => [
                'label' => 'Kupinda',
            ],

        ],

    ],

    'messages' => [

        'failed' => 'Zvinyorwa izvi hazvina kuwirirana nerecodhi yedu.',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Kuedza kupinda kwakawanda',
            'body' => 'Ndapota edza zvakare mushure memasekonzi :seconds.',
        ],

    ],

];