<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User Management: Store Managers add, edit and delete accounts and set their
 * roles. The routes require the "access-users" gate, so other roles get 403.
 * The panel must always keep at least one Store Manager.
 */
class UserController extends Controller
{
    private const LAST_STORE_MANAGER = 'The panel must always have at least one Store Manager. Make another user a Store Manager first.';

    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderBy('name')->orderBy('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        // The User model casts "password" as hashed, so it is never stored in plain text.
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        // The role is not mass assignable: it is set here, after UserRequest checked it.
        $user->role = $request->validated('role');
        $user->save();

        return redirect()->route('users.index')->with('status', "{$user->name} was added as {$user->role_label} and can now sign in.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $role = $request->validated('role');

        if ($role !== User::STORE_MANAGER && $user->isLastStoreManager()) {
            return back()->withInput()->withErrors(['role' => self::LAST_STORE_MANAGER]);
        }

        $user->fill($request->safe()->only(['name', 'email']));
        $user->role = $role;
        $user->save();

        // A Store Manager who gave themselves another role can no longer open User Management.
        // When they edited their own account, $user is the copy that has the new role.
        $actingUser = $user->is($request->user()) ? $user : $request->user();
        $redirect = $actingUser->can('access-users') ? route('users.index') : $actingUser->homeUrl();

        return redirect($redirect)->with('status', "{$user->name} was updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->isLastStoreManager()) {
            return back()->withErrors(['user' => self::LAST_STORE_MANAGER]);
        }

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account. Another Store Manager can delete it.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', "{$user->name} was deleted.");
    }
}
