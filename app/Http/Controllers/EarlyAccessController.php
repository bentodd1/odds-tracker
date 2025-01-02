<?php

namespace App\Http\Controllers;

use App\Models\EarlyAccessSignup;
use Illuminate\Http\Request;

class EarlyAccessController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:early_access_signups,email'
        ]);

        EarlyAccessSignup::create($validated);

        return back()->with('success', 'Thanks for signing up! We\'ll notify you when premium features are available.');
    }
    //
}
