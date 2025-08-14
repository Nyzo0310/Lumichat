<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        // fetch whatever you need, then:
        return view('appointments.index', [
          // compact('appointments') etc
        ]);
    }
}


