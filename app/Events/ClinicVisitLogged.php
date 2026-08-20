<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Clinic\Models\ClinicVisit;

class ClinicVisitLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(public ClinicVisit $visit) {}
}
