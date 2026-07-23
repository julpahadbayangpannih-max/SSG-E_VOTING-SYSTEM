<?php

namespace App\Http\Controllers;

use App\Support\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function show()
    {
        if (License::isActivated()) {
            return redirect()->route('home');
        }

        return view('license.activate');
    }

    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => ['required', 'string'],
        ]);

        if (! License::activate($request->license_key)) {
            return back()->withErrors(['license_key' => 'That license key is not valid for this installation.'])->withInput();
        }

        return redirect()->route('home')->with('license_activated', true);
    }
}
