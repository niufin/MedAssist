<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Consultation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'mrn' => ['nullable', 'string', 'max:50'],
        ]);

        $providedMrn = $request->input('mrn');

        $user = null;

        if ($providedMrn) {
            $existingPatient = User::where('role', User::ROLE_PATIENT)
                ->where('mrn', $providedMrn)
                ->first();

            if ($existingPatient) {
                $existingPatient->name = $request->name;
                $existingPatient->email = $request->email;
                $existingPatient->password = Hash::make($request->password);
                $existingPatient->status = User::STATUS_ACTIVE;
                $existingPatient->save();

                $user = $existingPatient;
            }
        }

        if (!$user) {
            $mrn = $providedMrn;

            if (!$mrn) {
                do {
                    $mrn = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
                } while (User::where('mrn', $mrn)->exists());
            } else {
                if (User::where('role', User::ROLE_PATIENT)->where('mrn', $mrn)->exists()) {
                    return redirect()->back()
                        ->withErrors(['mrn' => 'This MRN is already linked to another patient account.'])
                        ->withInput();
                }
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_PATIENT,
                'status' => User::STATUS_ACTIVE,
                'mrn' => $mrn,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        if ($providedMrn && !$user->visit_connected) {
            $updatedCount = Consultation::where('mrn', $providedMrn)
                ->where(function ($q) use ($user) {
                    $q->whereNull('patient_id')
                      ->orWhere('patient_id', '!=', $user->id);
                })
                ->update(['patient_id' => $user->id]);

            if ($updatedCount > 0) {
                $user->visit_connected = true;
                $user->save();
                session()->flash('status', "Account created and $updatedCount past visits connected!");
            }
        }

        return redirect(route('dashboard', absolute: false));
    }
}
