<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Hostels\Models\HostelOutPass;

class HostelOutPassRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public HostelOutPass $outPass) {}
}
