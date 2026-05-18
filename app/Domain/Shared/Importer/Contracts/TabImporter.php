<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Contracts;

use App\Domain\Shared\Importer\Models\ImportSourceFile;

interface TabImporter
{
    /**
     * Allow-listed tab name in the source workbook (e.g. RAW_All_Industry_Master).
     */
    public function sheetName(): string;

    /**
     * Target Laravel table this tab writes into (used for validation result tagging).
     */
    public function targetTable(): string;

    /**
     * Validate the parsed rows. Side-effect: write ImportValidationResult rows
     * for any issues. Return the rows that should be committed (caller may
     * choose to skip commit if any error-severity result was emitted).
     *
     * @param  list<array<string, mixed>>  $rows  rows keyed by header name
     * @return list<array<string, mixed>> rows to commit (typically the input minus rejected rows)
     */
    public function validate(array $rows, ImportSourceFile $sourceFile): array;

    /**
     * Persist validated rows into the target table. Must be idempotent
     * (upsert by candidate key). Returns the count of rows written.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function commit(array $rows, ImportSourceFile $sourceFile): int;
}
