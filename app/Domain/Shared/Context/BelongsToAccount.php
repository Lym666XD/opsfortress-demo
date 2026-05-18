<?php

declare(strict_types=1);

namespace App\Domain\Shared\Context;

use RuntimeException;

trait BelongsToAccount
{
    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope(new AccountScope);

        static::creating(function ($model): void {
            if ($model->getAttribute('customer_account_id') !== null) {
                return;
            }

            $context = app(AccountContext::class);

            if (! $context->hasAccount()) {
                throw new RuntimeException(sprintf(
                    'Cannot create %s without an account context.',
                    static::class,
                ));
            }

            $model->setAttribute('customer_account_id', $context->customerAccountId());
        });
    }
}
