<?php

namespace Luria\Modelmap;

use framework\Http\Controllers\Controller;
use Carbon\Carbon;

class ModelmapController extends Controller
{

    public function index($timezone)
    {
        echo Carbon::now($timezone)->toDateTimeString();
    }

}