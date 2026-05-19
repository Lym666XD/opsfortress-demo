<?php

declare(strict_types=1);

namespace App\Domain\Whs\Importer\Tabs;

use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\Shared\Importer\Concerns\NormalisesValues;
use App\Domain\Shared\Importer\Concerns\WritesValidationResults;
use App\Domain\Shared\Importer\Contracts\TabImporter;
use App\Domain\Shared\Importer\Models\ImportSourceFile;

/**
 * Imports the RAW_All_Occupation_Master tab from SRC-001
 * (OpsFortress_Central_Occupation_Industry_Source_Pack).
 *
 * Like [[IndustriesTabImporter]], the source tab is denormalised — the same
 * occupation may appear once per task it associates with. The importer dedupes
 * by `occupation_candidate_key`; task<->occupation linkage is rebuilt later
 * by the task_occupation_access importer (slice 4).
 *
 * Source-column -> target-column map (per column mapping XLSX v0.3):
 *   occupation_record_id      -> occupations.external_occupation_id
 *   occupation_group          -> occupations.occupation_group
 *   occupation_sub_group      -> occupations.occupation_sub_group
 *   occupation_leaf           -> occupations.occupation_leaf
 *   occupation_candidate_key  -> occupations.occupation_candidate_key  (upsert key)
 *   active_status             -> occupations.active_status (boolean)
 *
 * Source carries other cols (task_id, swms_view_access, menu_visibility,
 * collation_status, source_workbook, notes etc.) that belong to the task<->
 * occupation access map, not to the occupation master row. Those cols are
 * ignored here and re-read by the task_occupation_access importer in slice 4.
 */
final class OccupationsTabImporter implements TabImporter
{
    use NormalisesValues, WritesValidationResults;

    private const SHEET = 'RAW_All_Occupation_Master';

    public function sheetName(): string
    {
        return self::SHEET;
    }

    public function targetTable(): string
    {
        return 'occupations';
    }

    public function validate(array $rows, ImportSourceFile $sourceFile): array
    {
        $kept = [];
        $seenCandidateKeys = [];
        $seenExternalIds = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for 1-indexed, +1 for header row

            $candidateKey = $row['occupation_candidate_key'] ?? null;
            if (! is_string($candidateKey) || $candidateKey === '') {
                $this->recordResult($sourceFile, 'error', 'occupations.candidate_key_missing', $rowNumber, 'occupation_candidate_key', null, 'Row skipped: occupation_candidate_key is required for upsert.');

                continue;
            }

            if (($row['occupation_group'] ?? null) === null) {
                $this->recordResult($sourceFile, 'warning', 'occupations.group_missing', $rowNumber, 'occupation_group', null, 'occupation_group is empty; importing without group.');
            }

            $activeRaw = $row['active_status'] ?? null;
            if ($activeRaw !== null && ! $this->isCoercibleActiveStatus($activeRaw)) {
                $this->recordResult($sourceFile, 'warning', 'occupations.active_status_unrecognised', $rowNumber, 'active_status', (string) $activeRaw, "Unrecognised active_status value [{$activeRaw}]; defaulting to inactive.");
            }

            // Dedup within this import on candidate_key (the canonical identity).
            if (isset($seenCandidateKeys[$candidateKey])) {
                $this->recordResult($sourceFile, 'warning', 'occupations.duplicate_candidate_key_in_source', $rowNumber, 'occupation_candidate_key', $candidateKey, 'Duplicate occupation_candidate_key in source rows; first row wins for this import.');

                continue;
            }
            $seenCandidateKeys[$candidateKey] = true;

            // Dedup within this import on external_occupation_id (which is unique in DB
            // but observed to repeat across distinct candidate_keys in real source data,
            // e.g. OCC-001 appears against two different occupation leaves).
            // First row keeps the external_id; subsequent rows are imported with
            // external_occupation_id = null + warning.
            $externalId = $this->stringOrNull($row['occupation_record_id'] ?? null);
            if ($externalId !== null) {
                if (isset($seenExternalIds[$externalId])) {
                    $this->recordResult($sourceFile, 'warning', 'occupations.duplicate_external_id_in_source', $rowNumber, 'occupation_record_id', $externalId, "occupation_record_id [{$externalId}] already used by an earlier row in this import; importing row without external_occupation_id reference.");
                    $row['occupation_record_id'] = null;
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
            Occupation::query()->updateOrCreate(
                ['occupation_candidate_key' => (string) $row['occupation_candidate_key']],
                [
                    'external_occupation_id' => $this->stringOrNull($row['occupation_record_id'] ?? null),
                    'occupation_group' => $this->stringOrNull($row['occupation_group'] ?? null),
                    'occupation_sub_group' => $this->stringOrNull($row['occupation_sub_group'] ?? null),
                    'occupation_leaf' => $this->stringOrNull($row['occupation_leaf'] ?? null),
                    'active_status' => $this->coerceActiveStatus($row['active_status'] ?? null),
                ],
            );

            $written++;
        }

        return $written;
    }
}
