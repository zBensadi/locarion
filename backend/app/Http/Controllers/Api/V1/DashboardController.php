<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\Actions\GetDashboardDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetDashboardDataAction $action)
    {
        $data = $action->execute($request->user());

        return response()->json($data);
    }
}
