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

    /*
    |--------------------------------------------------------------------------
    | Chunked recovery-archive uploads
    |--------------------------------------------------------------------------
    |
    | "Restore from a file" never POSTs the whole .zip in one request: the
    | browser slices it and sends backup_chunk_bytes-sized pieces that the
    | server appends together (App\Http\Controllers\Platform\
    | PlatformBackupChunkController). Keeping each request small is what lets
    | a 500 MB archive get through a CDN that refuses bodies over 100 MB.
    |
    | backup_max_bytes is the ceiling for the whole assembled file — 500 MB,
    | the 512000 KB asked for on the backup screen.
    |
    */

    'backup_chunk_bytes' => (int) env('BACKUP_CHUNK_BYTES', 10 * 1024 * 1024),

    'backup_max_bytes' => (int) env('BACKUP_MAX_BYTES', 512000 * 1024),

];
