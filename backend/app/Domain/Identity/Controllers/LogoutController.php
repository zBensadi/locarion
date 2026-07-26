<?php

namespace App\Domain\Identity\Controllers;

use App\Domain\Identity\Actions\LogoutUserAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LogoutController extends Controller
{
    public function __invoke(Request $request, LogoutUserAction $action)
    {
        $action->execute();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
