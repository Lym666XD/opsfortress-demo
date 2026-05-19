<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Concerns;

use App\Domain\Shared\Importer\Models\ImportSourceFile;
use App\Domain\Shared\Importer\Models\ImportValidationResult;

/**
 * Shared validation-result writer for TabImporter implementations.
 *
 * Concrete importers call recordResult() to attach an
 * ImportValidationResult row to the current batch. The host class must
 * also expose sheetName() and targetTable() (already required by the
 * TabImporter interface) so the result row is tagged correctly without
 * the caller having to repeat them on every call.
 */
trait WritesValidationResults
{
    protected function recordResult(
        ImportSourceFile $sourceFile,
        string $severity,
        string $ruleCode,
        int $rowNumber,
        string $columnName,
        ?string $rawValue,
        string $message,
    ): void {
        ImportValidationResult::create([
            'import_batch_id' => $sourceFile->import_batch_id,
            'import_source_file_id' => $sourceFile->id,
            'severity' => $severity,
            'rule_code' => $ruleCode,
            'message' => $message,
            'source_sheet_name' => $this->sheetName(),
            'source_row_number' => $rowNumber,
            'source_column_name' => $columnName,
            'target_table' => $this->targetTable(),
            'raw_value' => $rawValue,
        ]);
    }
}
