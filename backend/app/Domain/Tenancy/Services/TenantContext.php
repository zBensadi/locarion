<?php

namespace App\Domain\Tenancy\Services;

class TenantContext
{
    protected ?string $agencyId = null;

    /**
     * Set the current tenant (agency) ID.
     */
    public function setAgencyId(?string $agencyId): void
    {
        $this->agencyId = $agencyId;
    }

    /**
     * Get the current tenant (agency) ID.
     */
    public function getAgencyId(): ?string
    {
        return $this->agencyId;
    }
}
