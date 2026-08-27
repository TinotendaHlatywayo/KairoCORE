<?php

return [

    'title' => 'Simbisa kero yako yeemail',

    'heading' => 'Simbisa kero yako yeemail',

    'actions' => [

        'resend_notification' => [
            'label' => 'Tumira zvakare',
        ],

    ],

    'messages' => [
        'notification_not_received' => 'Hauna kuwana email yataka tumira?',
        'notification_sent' => 'Tatuma email ku :email ine mirayiridzo yekusimbisa kero yako yeemail.',
    ],

    'notifications' => [

        'notification_resent' => [
            'title' => 'Tatuma email zvakare.',
        ],

        'notification_resend_throttled' => [
            'title' => 'Kuedza kutumira zvakare kwakawanda',
            'body' => 'Ndapota edza zvakare mushure memasekonzi :seconds.',
        ],

    ],

];