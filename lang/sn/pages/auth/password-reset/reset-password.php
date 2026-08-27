<?php

return [

    'title' => 'Dzorera password yako',

    'heading' => 'Dzorera password yako',

    'form' => [

        'email' => [
            'label' => 'Kero yeemail',
        ],

        'password' => [
            'label' => 'Password',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Simbisa password',
        ],

        'actions' => [

            'reset' => [
                'label' => 'Dzorera password',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Kuedza kudzorera kwakawanda',
            'body' => 'Ndapota edza zvakare mushure memasekonzi :seconds.',
        ],

    ],

];