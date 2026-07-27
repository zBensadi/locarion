<?php

namespace App\Domain\Identity\Controllers;

use App\Domain\Identity\Actions\LoginUserAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    public function __invoke(Request $request, LoginUserAction $action)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = $action->execute($credentials, $request);

        // Eager load roles/permissions so the SPA can render role-appropriate UI
        $user->load('roles', 'permissions');

        return response()->json([
            'user' => $user,
        ]);
    }
}
