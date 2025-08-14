<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:tbl_registration,email'],
            'contact_number' => ['required', 'string', 'max:20'],
            'course' => ['required', 'string'],
            'year_level' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Registration::create([
            'full_name'      => $request->full_name,
            'email'          => $request->email,
            'contact_number' => $request->contact_number,
            'course'         => $request->course,
            'year_level'     => $request->year_level,
            'password'       => Hash::make($request->password),
        ]);

        User::create([
            'name'     => $request->full_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        return redirect()->route('login')->with('success', 'Your account has been created!');
    }

}
