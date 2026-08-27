<?php

namespace App\Providers;

use App\Events\HostelOutPassRequested;
use App\Listeners\SendHostelOutPassNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        HostelOutPassRequested::class => [
            SendHostelOutPassNotifications::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
