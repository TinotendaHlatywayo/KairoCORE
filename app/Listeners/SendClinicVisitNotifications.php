<?php

namespace App\Listeners;

use App\Events\ClinicVisitLogged;
use App\Notifications\ClinicVisitNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClinicVisitNotifications implements ShouldQueue
{
    public function handle(ClinicVisitLogged $event): void
    {
        $student = $event->visit->student;
        $parent = $student->guardians()->first();

        if ($parent) {
            $parent->notify(new ClinicVisitNotification($event->visit));
        }
    }
}
