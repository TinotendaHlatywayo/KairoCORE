<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ApplicationSuccessModal extends Component
{
    public $applicationNumber;

    public $studentName;

    public function __construct($applicationNumber = null, $studentName = null)
    {
        $this->applicationNumber = $applicationNumber;
        $this->studentName = $studentName;
    }

    public function render()
    {
        return view('components.application-success-modal');
    }
}
