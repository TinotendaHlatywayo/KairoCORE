<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IndexNow key
    |--------------------------------------------------------------------------
    |
    | Public key file lives at public/{key}.txt — Bing and Yandex use it to
    | verify domain ownership before accepting instant-recrawl submissions.
    | Run `php artisan seo:indexnow` after publishing content changes.
    |
    */

    'indexnow_key' => env('INDEXNOW_KEY', '94685a4011304de8b4dce5c754edbcb2'),

];
