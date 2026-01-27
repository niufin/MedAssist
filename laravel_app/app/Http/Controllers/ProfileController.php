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
        $skipped = [];
        if (!Schema::hasColumn('users', 'designation')) {
            unset($validated['designation']);
        }
        if (!Schema::hasColumn('users', 'additional_qualifications')) {
            unset($validated['additional_qualifications']);
        }
        foreach (['clinic_address', 'clinic_contact_number', 'clinic_email', 'clinic_registration_number', 'clinic_gstin'] as $field) {
            if (!Schema::hasColumn('users', $field)) {
                if (array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '') {
                    $skipped[] = $field;
                }
                unset($validated[$field]);
            }
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if (!empty($skipped)) {
            return Redirect::route('profile.edit')->with('error', 'Clinic profile details were not saved because the database is missing required columns. Run: php artisan migrate --force');
        }

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
