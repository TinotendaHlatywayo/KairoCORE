<?php

namespace App\Providers;

use App\Events\OutPassRequested;
use App\Listeners\ProcessOutPassNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OutPassRequested::class => [
            ProcessOutPassNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
