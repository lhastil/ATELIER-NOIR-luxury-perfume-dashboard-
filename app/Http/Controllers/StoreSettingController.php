<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The store's basic information: name, contact details and logo.
 */
class StoreSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['settings' => StoreSetting::current()]);
    }

    public function update(StoreSettingRequest $request): RedirectResponse
    {
        $settings = StoreSetting::current();
        $previousLogo = $settings->logo_path;

        // Only the validated text fields are filled; the logo path is never taken from the request.
        $settings->fill($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            // Stored under a random name chosen by Laravel, e.g. store-logos/abc123.png.
            $path = $request->file('logo')->store(StoreSetting::LOGO_DIRECTORY, 'public');

            if ($path === false) {
                return back()->withInput()->withErrors(['logo' => 'The logo could not be uploaded. Please try again.']);
            }

            $settings->logo_path = $path;
        }

        $settings->save();

        // The old logo is removed only after the new one is stored and saved,
        // and only if it is one of the logos this page uploaded.
        if ($previousLogo && $previousLogo !== $settings->logo_path
            && str_starts_with($previousLogo, StoreSetting::LOGO_DIRECTORY.'/')) {
            Storage::disk('public')->delete($previousLogo);
        }

        return redirect()->route('settings.edit')->with('status', 'The store settings were saved.');
    }
}
