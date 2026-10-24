<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hard upload ceiling
    |--------------------------------------------------------------------------
    |
    | The smallest of the PHP, Livewire and proxy/CDN body limits is what an
    | upload can actually reach, and it is the number quoted back to the user
    | when an upload is rejected (see App\Support\UploadReason). Set this when
    | something in front of PHP enforces a lower cap than PHP itself —
    | Cloudflare, for example, refuses request bodies over ~100 MB on the
    | standard plans, so production pins the limit there even though PHP would
    | happily accept more.
    |
    | Leave it null to report only PHP's and Livewire's limits.
    |
    */

    'max_megabytes' => env('UPLOAD_MAX_MB'),

];
