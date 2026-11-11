<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's own profile and password. The account is always taken
 * from the session, never from the form, so users can only change their own.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only(['name', 'email']));

        return redirect()->route('profile.edit')->with('status', 'Your profile was updated.');
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        // The User model casts "password" as hashed, so it is never stored in plain text.
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()->route('profile.edit')->with('status', 'Your password was changed.');
    }
}
