<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfilePhotoRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * The signed-in user's own profile and password. The account is always taken
 * from the session, never from the form, so users can only change their own.
 */
class ProfileController extends Controller
{
    /**
     * Folder on the "public" disk for profile photos.
     */
    private const PHOTO_DIRECTORY = 'profile-photos';

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

    /**
     * Adds or replaces the signed-in user's photo. The new file is stored first;
     * the old one is deleted only once the account points to the new one.
     */
    public function updatePhoto(ProfilePhotoRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->profile_photo_path;

        // Stored under a random name chosen by Laravel, e.g. profile-photos/abc123.jpg.
        $path = $request->file('photo')->store(self::PHOTO_DIRECTORY, 'public');

        if ($path === false) {
            return back()->withErrors(['photo' => 'The photo could not be uploaded. Please try again.']);
        }

        try {
            $user->forceFill(['profile_photo_path' => $path])->saveOrFail();
        } catch (Throwable $exception) {
            // The account keeps its previous photo, so only the new upload is removed.
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        $this->deletePhoto($previousPath);

        return redirect()->route('profile.edit')->with('status', $previousPath ? 'Your profile photo was replaced.' : 'Your profile photo was added.');
    }

    /**
     * Removes the signed-in user's photo: the account is updated first, then the file deleted.
     */
    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->profile_photo_path;

        if ($previousPath) {
            $user->forceFill(['profile_photo_path' => null])->saveOrFail();
            $this->deletePhoto($previousPath);
        }

        return redirect()->route('profile.edit')->with('status', 'Your profile photo was removed.');
    }

    /**
     * Deletes a photo file, but only one this page stored (profile-photos/<name>):
     * any other path is left alone.
     */
    private function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, self::PHOTO_DIRECTORY.'/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
