<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class UploadProgress extends Component
{
    public function __construct(...$args)
    {
        //
    }

    public function render(): View
    {
        return view('components.upload-progress');
    }
}
