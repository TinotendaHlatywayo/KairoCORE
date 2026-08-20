<?php

namespace App\Listeners;

use App\Events\HostelOutPassRequested;
use App\Notifications\HostelOutPassOtpNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendHostelOutPassNotifications implements ShouldQueue
{
    public function handle(HostelOutPassRequested $event): void
    {
        $student = $event->outPass->student;
        $parent = $student->guardians()->first();

        if ($parent) {
            $parent->notify(new HostelOutPassOtpNotification($event->outPass));
        }
    }
}
