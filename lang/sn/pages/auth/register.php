<?php

return [

    'title' => 'Nyorera',

    'heading' => 'Nyorera',

    'actions' => [

        'login' => [
            'before' => 'kana',
            'label' => 'pinda muakaundi yako',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Kero yeemail',
        ],

        'name' => [
            'label' => 'Zita',
        ],

        'password' => [
            'label' => 'Password',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Simbisa password',
        ],

        'actions' => [

            'register' => [
                'label' => 'Nyorera',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Kuedza kunyorera kwakawanda',
            'body' => 'Ndapota edza zvakare mushure memasekonzi :seconds.',
        ],

    ],

];