<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Lets a signed-in administrator add another administrator account.
 */
class UserController extends Controller
{
    public function create(): View
    {
        return view('users.create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        // The User model casts "password" as hashed, so it is never stored in plain text.
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        return redirect()->route('users.create')->with('status', "{$user->name} was added and can now sign in.");
    }
}
