<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\PlatformAdmin\Actions\CreateAgencyAction;
use App\Domain\PlatformAdmin\Requests\AgencyRequest;
use App\Domain\PlatformAdmin\Resources\AgencyResource;
use App\Domain\Tenancy\Models\Agency;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgencyController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Agency::class);

        // Lightweight statistics
        $stats = [
            'total' => Agency::count(),
            'active' => Agency::where('status', 'active')->count(),
            'inactive' => Agency::where('status', 'inactive')->count(),
        ];

        $query = Agency::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        $agencies = $query->latest()->paginate(15);

        return response()->json([
            'data' => AgencyResource::collection($agencies),
            'meta' => [
                'current_page' => $agencies->currentPage(),
                'last_page' => $agencies->lastPage(),
                'total' => $agencies->total(),
            ],
            'stats' => $stats,
        ]);
    }

    public function store(AgencyRequest $request, CreateAgencyAction $action)
    {
        Gate::authorize('create', Agency::class);
        
        $result = $action->execute($request->validated());

        return response()->json([
            'agency' => new AgencyResource($result['agency']),
            'admin' => $result['admin'],
        ], 201);
    }

    public function show(Agency $agency)
    {
        Gate::authorize('view', $agency);

        return new AgencyResource($agency);
    }

    public function update(AgencyRequest $request, Agency $agency)
    {
        Gate::authorize('update', $agency);
        
        $agency->update($request->validated());

        return new AgencyResource($agency);
    }
}
