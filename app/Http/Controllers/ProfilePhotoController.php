<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePhotoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfilePhotoController extends Controller
{
    /**
     * Serve the signed-in user's own photo. Photos are kept on the private disk, so they are
     * only reachable through this route and only by their owner.
     */
    public function show(Request $request): Response
    {
        $path = $request->user()->profile_photo_path;

        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * Replace the signed-in user's photo with the uploaded one.
     */
    public function update(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->profile_photo_path;

        $user->forceFill([
            'profile_photo_path' => $request->file('photo')->store('profile-photos', 'local'),
        ])->save();

        if ($previousPath !== null) {
            Storage::disk('local')->delete($previousPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    /**
     * Remove the signed-in user's photo, going back to their initials.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path !== null) {
            Storage::disk('local')->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-photo-removed');
    }
}
