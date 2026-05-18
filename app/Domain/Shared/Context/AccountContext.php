<?php

declare(strict_types=1);

namespace App\Domain\Shared\Context;

final class AccountContext
{
    private ?string $accountId = null;

    private ?string $businessEntityId = null;

    private ?string $workplaceId = null;

    public function set(?string $accountId, ?string $businessEntityId = null, ?string $workplaceId = null): void
    {
        $this->accountId = $accountId;
        $this->businessEntityId = $businessEntityId;
        $this->workplaceId = $workplaceId;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
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
        return $this->accountId !== null;
    }

    public function runAs(?string $accountId, callable $callback, ?string $businessEntityId = null, ?string $workplaceId = null): mixed
    {
        $previousCustomerAccountId = $this->accountId;
        $previousBusinessEntityId = $this->businessEntityId;
        $previousWorkplaceId = $this->workplaceId;

        $this->set($accountId, $businessEntityId, $workplaceId);

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
