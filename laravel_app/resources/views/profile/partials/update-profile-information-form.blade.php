<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="$user->isHospitalAdmin() ? __('Hospital / Clinic Name') : __('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if($user->isDoctor())
            <div>
                <x-input-label for="medical_center_name" :value="__('Medical Center Name')" />
                <x-text-input id="medical_center_name" name="medical_center_name" type="text" class="mt-1 block w-full" :value="old('medical_center_name', $user->medical_center_name)" />
                <x-input-error class="mt-2" :messages="$errors->get('medical_center_name')" />
            </div>

            <div>
                <x-input-label for="degrees" :value="__('Degrees')" />
                <x-text-input id="degrees" name="degrees" type="text" class="mt-1 block w-full" :value="old('degrees', $user->degrees)" />
                <x-input-error class="mt-2" :messages="$errors->get('degrees')" />
            </div>

            <div>
                <x-input-label for="designation" :value="__('Designation')" />
                <x-text-input id="designation" name="designation" type="text" class="mt-1 block w-full" :value="old('designation', $user->designation)" />
                <x-input-error class="mt-2" :messages="$errors->get('designation')" />
            </div>

            <div>
                <x-input-label for="additional_qualifications" :value="__('Additional Courses / Diplomas')" />
                <x-text-input id="additional_qualifications" name="additional_qualifications" type="text" class="mt-1 block w-full" :value="old('additional_qualifications', $user->additional_qualifications)" />
                <x-input-error class="mt-2" :messages="$errors->get('additional_qualifications')" />
            </div>

            <div>
                <x-input-label for="license_number" :value="__('License Number / Reg No')" />
                <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full" :value="old('license_number', $user->license_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('license_number')" />
            </div>

            <div>
                <x-input-label for="contact_number" :value="__('Contact Number')" />
                <x-text-input id="contact_number" name="contact_number" type="text" class="mt-1 block w-full" :value="old('contact_number', $user->contact_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('contact_number')" />
            </div>
        @endif

        @if($user->isHospitalAdmin())
            <div>
                <x-input-label for="clinic_address" :value="__('Clinic Address')" />
                <x-text-input id="clinic_address" name="clinic_address" type="text" class="mt-1 block w-full" :value="old('clinic_address', $user->clinic_address)" />
                <x-input-error class="mt-2" :messages="$errors->get('clinic_address')" />
            </div>

            <div>
                <x-input-label for="clinic_contact_number" :value="__('Clinic Contact Number')" />
                <x-text-input id="clinic_contact_number" name="clinic_contact_number" type="text" class="mt-1 block w-full" :value="old('clinic_contact_number', $user->clinic_contact_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('clinic_contact_number')" />
            </div>

            <div>
                <x-input-label for="clinic_email" :value="__('Clinic Email ID')" />
                <x-text-input id="clinic_email" name="clinic_email" type="email" class="mt-1 block w-full" :value="old('clinic_email', $user->clinic_email)" />
                <x-input-error class="mt-2" :messages="$errors->get('clinic_email')" />
            </div>

            <div>
                <x-input-label for="clinic_registration_number" :value="__('Clinic Certificate / Registration Number')" />
                <x-text-input id="clinic_registration_number" name="clinic_registration_number" type="text" class="mt-1 block w-full" :value="old('clinic_registration_number', $user->clinic_registration_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('clinic_registration_number')" />
            </div>

            <div>
                <x-input-label for="clinic_gstin" :value="__('Clinic GSTIN')" />
                <x-text-input id="clinic_gstin" name="clinic_gstin" type="text" class="mt-1 block w-full" :value="old('clinic_gstin', $user->clinic_gstin)" />
                <x-input-error class="mt-2" :messages="$errors->get('clinic_gstin')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
