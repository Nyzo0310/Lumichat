<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AppointmentController extends Controller
{
    public function index()
    {
        // No data yet; just render the table structure/UI
        return view('admin.appointments.index');
    }
}
