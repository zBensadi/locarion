<?php

namespace App\Domain\Identity\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * Attempt to log in a user.
     *
     * @return \App\Domain\Identity\Models\User
     *
     * @throws ValidationException
     */
    public function execute(array $credentials, bool $remember = false)
    {
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => __('Your account is inactive.'),
                ]);
            }

            request()->session()->regenerate();

            return $user;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }
}
