<?php

namespace Azuriom\Plugin\InspiratoStats\Controllers;

use Azuriom\Http\Controllers\Controller;

class InspiratoStatsHomeController extends Controller
{
    /**
     * Show the home plugin page.
     */
    public function index()
    {
        return view('inspiratostats::index');
    }
}
