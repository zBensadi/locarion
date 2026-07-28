<?php

namespace App\Domain\Dashboard\Actions;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Resources\ReservationResource;
use App\Domain\Fleet\Resources\VehicleResource;
use App\Domain\Identity\Models\User;
use App\Domain\PlatformAdmin\Resources\AgencyResource;
use App\Domain\Tenancy\Models\Agency;
use App\Domain\Tenancy\Scopes\TenantScope;

class GetDashboardDataAction
{
    public function execute(User $user): array
    {
        if ($user->hasRole('super-admin')) {
            return $this->getSuperAdminData();
        }

        return $this->getAgencyAdminData($user->agency_id);
    }

    private function getSuperAdminData(): array
    {
        // Platform wide statistics
        $stats = [
            'total_agencies' => Agency::count(),
            'active_agencies' => Agency::where('status', 'active')->count(),
            'total_vehicles' => Vehicle::withoutGlobalScope(TenantScope::class)->count(),
            'total_reservations' => Reservation::withoutGlobalScope(TenantScope::class)->count(),
            'total_users' => User::count(),
        ];

        // Recent Agencies
        $recentAgencies = Agency::latest()->take(5)->get();

        // Recent Reservations
        $recentReservations = Reservation::withoutGlobalScope(TenantScope::class)
            ->with(['vehicle.category', 'agency'])
            ->latest()
            ->take(5)
            ->get();

        // Activity timestamps
        $activity = [
            'recent_vehicle_created_at' => Vehicle::withoutGlobalScope(TenantScope::class)->latest()->value('created_at'),
            'recent_reservation_created_at' => Reservation::withoutGlobalScope(TenantScope::class)->latest()->value('created_at'),
            'recent_agency_created_at' => Agency::latest()->value('created_at'),
        ];

        return [
            'role' => 'super-admin',
            'stats' => $stats,
            'recent_agencies' => AgencyResource::collection($recentAgencies),
            'recent_reservations' => ReservationResource::collection($recentReservations),
            'activity' => $activity,
        ];
    }

    private function getAgencyAdminData(?string $agencyId): array
    {
        // If they don't have an agency (shouldn't happen for agency-admin), return empty stats
        if (!$agencyId) {
            return ['role' => 'agency-admin', 'stats' => [], 'recent_reservations' => [], 'recent_vehicles' => [], 'attention_vehicles' => []];
        }

        // Tenant scope is automatically applied to Vehicle and Reservation
        $stats = [
            'total_vehicles' => Vehicle::count(),
            'available_vehicles' => Vehicle::where('status', 'available')->count(),
            'reserved_vehicles' => Vehicle::where('status', 'reserved')->count(),
            'maintenance_vehicles' => Vehicle::where('status', 'maintenance')->count(),
            'pending_reservations' => Reservation::where('status', 'pending')->count(),
            'confirmed_reservations' => Reservation::where('status', 'confirmed')->count(),
            'completed_reservations' => Reservation::where('status', 'completed')->count(),
        ];

        $recentReservations = Reservation::with(['vehicle.category'])
            ->latest()
            ->take(5)
            ->get();

        $recentVehicles = Vehicle::with(['category'])
            ->latest()
            ->take(5)
            ->get();

        $attentionVehicles = Vehicle::with(['category'])
            ->whereIn('status', ['maintenance', 'retired'])
            ->latest()
            ->take(5)
            ->get();

        return [
            'role' => 'agency-admin',
            'stats' => $stats,
            'recent_reservations' => ReservationResource::collection($recentReservations),
            'recent_vehicles' => VehicleResource::collection($recentVehicles),
            'attention_vehicles' => VehicleResource::collection($attentionVehicles),
        ];
    }
}
