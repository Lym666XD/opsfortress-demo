<?php

declare(strict_types=1);

namespace App\Domain\Whs\Importer\Tabs;

use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Domain\Shared\Importer\Contracts\TabImporter;
use App\Domain\Shared\Importer\Models\ImportSourceFile;
use App\Domain\Shared\Importer\Models\ImportValidationResult;

/**
 * Imports the RAW_All_Industry_Master tab from SRC-001
 * (OpsFortress_Central_Occupation_Industry_Source_Pack).
 *
 * The source tab is denormalised: the same industry leaf may appear multiple
 * times (once per task it associates with). The importer dedupes by
 * `industry_candidate_key` so each industry row lands in `industries` only
 * once, then the task<->industry linkage is rebuilt later by
 * task_industry_access import (slice 4).
 *
 * Source-column → target-column map (per column mapping XLSX v0.3):
 *   industry_record_id      -> industries.external_industry_id
 *   industry_group          -> industries.industry_group
 *   industry_sub_group      -> industries.industry_sub_group
 *   industry_leaf           -> industries.industry_leaf
 *   industry_candidate_key  -> industries.industry_candidate_key  (upsert key)
 *   active_status           -> industries.active_status (boolean)
 *
 * The source carries other cols (task_id, swms_applicability, menu_visibility,
 * collation_status, source_workbook, notes etc.) that belong to the task<->
 * industry access map, not to the industry master row. Those cols are
 * ignored here and re-read by the task_industry_access importer in slice 4.
 */
final class IndustriesTabImporter implements TabImporter
{
    private const SHEET = 'RAW_All_Industry_Master';

    public function sheetName(): string
    {
        return self::SHEET;
    }

    public function targetTable(): string
    {
        return 'industries';
    }

    public function validate(array $rows, ImportSourceFile $sourceFile): array
    {
        $kept = [];
        $seenCandidateKeys = [];
        $seenExternalIds = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for 1-indexed, +1 for header row

            $candidateKey = $row['industry_candidate_key'] ?? null;
            if (! is_string($candidateKey) || $candidateKey === '') {
                $this->recordResult($sourceFile, 'error', 'industries.candidate_key_missing', $rowNumber, 'industry_candidate_key', null, 'Row skipped: industry_candidate_key is required for upsert.');

                continue;
            }

            if (($row['industry_group'] ?? null) === null) {
                $this->recordResult($sourceFile, 'warning', 'industries.group_missing', $rowNumber, 'industry_group', null, 'industry_group is empty; importing without group.');
            }

            $activeRaw = $row['active_status'] ?? null;
            if ($activeRaw !== null && ! $this->isCoercibleActiveStatus($activeRaw)) {
                $this->recordResult($sourceFile, 'warning', 'industries.active_status_unrecognised', $rowNumber, 'active_status', (string) $activeRaw, "Unrecognised active_status value [{$activeRaw}]; defaulting to inactive.");
            }

            // Dedup within this import on candidate_key (the canonical identity).
            if (isset($seenCandidateKeys[$candidateKey])) {
                $this->recordResult($sourceFile, 'warning', 'industries.duplicate_candidate_key_in_source', $rowNumber, 'industry_candidate_key', $candidateKey, 'Duplicate industry_candidate_key in source rows; first row wins for this import.');

                continue;
            }
            $seenCandidateKeys[$candidateKey] = true;

            // Dedup within this import on external_industry_id (which is unique in DB
            // but observed to repeat across distinct candidate_keys in real source data).
            // First row keeps the external_id; subsequent rows are imported with
            // external_industry_id = null + warning.
            $externalId = $this->stringOrNull($row['industry_record_id'] ?? null);
            if ($externalId !== null) {
                if (isset($seenExternalIds[$externalId])) {
                    $this->recordResult($sourceFile, 'warning', 'industries.duplicate_external_id_in_source', $rowNumber, 'industry_record_id', $externalId, "industry_record_id [{$externalId}] already used by an earlier row in this import; importing row without external_industry_id reference.");
                    $row['industry_record_id'] = null;
                } else {
                    $seenExternalIds[$externalId] = true;
                }
            }

            $kept[] = $row;
        }

        return $kept;
    }

    public function commit(array $rows, ImportSourceFile $sourceFile): int
    {
        $written = 0;

        foreach ($rows as $row) {
            Industry::query()->updateOrCreate(
                ['industry_candidate_key' => (string) $row['industry_candidate_key']],
                [
                    'external_industry_id' => $this->stringOrNull($row['industry_record_id'] ?? null),
                    'industry_group' => $this->stringOrNull($row['industry_group'] ?? null),
                    'industry_sub_group' => $this->stringOrNull($row['industry_sub_group'] ?? null),
                    'industry_leaf' => $this->stringOrNull($row['industry_leaf'] ?? null),
                    'active_status' => $this->coerceActiveStatus($row['active_status'] ?? null),
                ],
            );

            $written++;
        }

        return $written;
    }

    private function recordResult(
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
            'source_sheet_name' => self::SHEET,
            'source_row_number' => $rowNumber,
            'source_column_name' => $columnName,
            'target_table' => $this->targetTable(),
            'raw_value' => $rawValue,
        ]);
    }

    private function isCoercibleActiveStatus(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['active', 'inactive', 'yes', 'no', 'true', 'false', '1', '0'], true);
    }

    private function coerceActiveStatus(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['active', 'yes', 'true', '1'], true);
        }

        return false;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}
