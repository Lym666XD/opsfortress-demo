<?php

declare(strict_types=1);

namespace App\Domain\Shared\Context;

final class AccountContext
{
    private ?string $customerAccountId = null;

    private ?string $businessEntityId = null;

    private ?string $workplaceId = null;

    public function set(?string $customerAccountId, ?string $businessEntityId = null, ?string $workplaceId = null): void
    {
        $this->customerAccountId = $customerAccountId;
        $this->businessEntityId = $businessEntityId;
        $this->workplaceId = $workplaceId;
    }

    public function customerAccountId(): ?string
    {
        return $this->customerAccountId;
    }

    public function businessEntityId(): ?string
    {
        return $this->businessEntityId;
    }

    public function workplaceId(): ?string
    {
        return $this->workplaceId;
    }

    public function hasAccount(): bool
    {
        return $this->customerAccountId !== null;
    }

    public function runAs(?string $customerAccountId, callable $callback, ?string $businessEntityId = null, ?string $workplaceId = null): mixed
    {
        $previousCustomerAccountId = $this->customerAccountId;
        $previousBusinessEntityId = $this->businessEntityId;
        $previousWorkplaceId = $this->workplaceId;

        $this->set($customerAccountId, $businessEntityId, $workplaceId);

        try {
            return $callback();
        } finally {
            $this->set($previousCustomerAccountId, $previousBusinessEntityId, $previousWorkplaceId);
        }
    }

    public function clear(): void
    {
        $this->set(null);
    }
}
