<?php

declare(strict_types=1);

namespace App\Domain\Shared\Context;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class AccountScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(AccountContext::class);

        if (! $context->hasAccount()) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('account_id'),
            $context->accountId(),
        );
    }
}
