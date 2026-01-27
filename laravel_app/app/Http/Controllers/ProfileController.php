<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        if (!Schema::hasColumn('users', 'designation')) {
            unset($validated['designation']);
        }
        if (!Schema::hasColumn('users', 'additional_qualifications')) {
            unset($validated['additional_qualifications']);
        }
        if (!Schema::hasColumn('users', 'clinic_address')) {
            unset($validated['clinic_address']);
        }
        if (!Schema::hasColumn('users', 'clinic_contact_number')) {
            unset($validated['clinic_contact_number']);
        }
        if (!Schema::hasColumn('users', 'clinic_email')) {
            unset($validated['clinic_email']);
        }
        if (!Schema::hasColumn('users', 'clinic_registration_number')) {
            unset($validated['clinic_registration_number']);
        }
        if (!Schema::hasColumn('users', 'clinic_gstin')) {
            unset($validated['clinic_gstin']);
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user() && $request->user()->isSuperAdmin()) {
            Log::warning('user.delete.blocked', [
                'reason' => 'super_admin_self_delete',
                'user_id' => $request->user()->id,
                'role' => $request->user()->role,
                'ip' => $request->ip(),
            ]);
            return Redirect::route('profile.edit')->with('error', 'Super admin account cannot be deleted.');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Log::warning('user.delete.requested', [
            'user_id' => $user?->id,
            'role' => $user?->role,
            'ip' => $request->ip(),
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
