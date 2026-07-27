<?php

namespace App\Domain\Identity\Actions;

use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    /**
     * Log out the currently authenticated user.
     *
     * @return void
     */
    public function execute(\Illuminate\Http\Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
