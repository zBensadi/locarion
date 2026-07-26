<?php

namespace App\Domain\Identity\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        
        $user->load('roles', 'permissions');

        return response()->json([
            'user' => $user
        ]);
    }
}
