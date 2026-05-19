<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Concerns;

/**
 * Shared value coercion for TabImporter implementations.
 *
 * Source workbooks carry strings ("Active", "Yes", "true", "1") where the
 * DBML expects booleans, and may carry empty strings where we want nulls.
 * Each importer needs the same coercion, so it lives here rather than
 * being copy-pasted per-tab.
 */
trait NormalisesValues
{
    protected function isCoercibleActiveStatus(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['active', 'inactive', 'yes', 'no', 'true', 'false', '1', '0'], true);
    }

    protected function coerceActiveStatus(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['active', 'yes', 'true', '1'], true);
        }

        return false;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}
