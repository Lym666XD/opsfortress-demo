<?php

declare(strict_types=1);

namespace App\Domain\Whs\Importer\Tabs;

use App\Domain\Shared\Importer\Concerns\NormalisesValues;
use App\Domain\Shared\Importer\Concerns\WritesValidationResults;
use App\Domain\Shared\Importer\Contracts\TabImporter;
use App\Domain\Shared\Importer\Models\ImportSourceFile;
use App\Domain\Whs\Tasks\Models\Task;

/**
 * Imports the RAW_All_Task_Register tab from SRC-001
 * (OpsFortress_Central_Occupation_Industry_Source_Pack).
 *
 * Unlike Industries/Occupations, the DBML makes `external_task_id` NOT NULL +
 * unique, so it is the canonical identity and the upsert key. `task_name` is
 * also NOT NULL. Rows missing either are rejected with an error.
 *
 * Source-column → target-column map (per column mapping XLSX v0.3, row 81-86):
 *   task_id              -> tasks.external_task_id  (upsert key, NOT NULL)
 *   task_name            -> tasks.task_name         (NOT NULL)
 *   task_title           -> tasks.task_title
 *   document_type        -> tasks.document_type
 *   trade_industry       -> tasks.trade_industry
 *   task_group           -> tasks.task_group
 *   task_sub_group       -> tasks.task_sub_group
 *   task_leaf            -> tasks.task_leaf
 *   task_candidate_key   -> tasks.task_candidate_key
 *   active_status        -> tasks.active_status (boolean)
 *
 * Source has 34 cols; the 24 not listed above are deliberately ignored:
 *   - P1 / future fields:   duty_holder_role, exposed_persons, risk_basis,
 *                           control_basis, verification_required,
 *                           verification_method, evidence_required,
 *                           evidence_type, responsible_role, review_frequency,
 *                           expiry_date, status (workflow lifecycle, distinct
 *                           from active_status), failed_control_action,
 *                           escalation_required, alert_trigger,
 *                           dashboard_metric, audit_event_required,
 *                           privacy_access_level, approved_by_role, version
 *   - Other target tables: task_register_id (governance only, not the FK key)
 *   - Source-pack governance: source_record_id, related_laravel_table, notes
 *
 * No within-source dedup is performed: the DBML's unique constraint on
 * `external_task_id` is strict, so a duplicate `task_id` in the source is
 * treated as a hard error rather than coerced (different from Industries/
 * Occupations where the external_id was advisory).
 */
final class TasksTabImporter implements TabImporter
{
    use NormalisesValues, WritesValidationResults;

    private const SHEET = 'RAW_All_Task_Register';

    public function sheetName(): string
    {
        return self::SHEET;
    }

    public function targetTable(): string
    {
        return 'tasks';
    }

    public function validate(array $rows, ImportSourceFile $sourceFile): array
    {
        $kept = [];
        $seenExternalIds = [];
        $seenCandidateKeys = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for 1-indexed, +1 for header row

            $externalTaskId = $this->stringOrNull($row['task_id'] ?? null);
            if ($externalTaskId === null) {
                $this->recordResult($sourceFile, 'error', 'tasks.external_task_id_missing', $rowNumber, 'task_id', null, 'Row skipped: task_id is required (becomes tasks.external_task_id, which is NOT NULL).');

                continue;
            }

            $taskName = $this->stringOrNull($row['task_name'] ?? null);
            if ($taskName === null) {
                $this->recordResult($sourceFile, 'error', 'tasks.task_name_missing', $rowNumber, 'task_name', null, "Row skipped: task_name is required for tasks.external_task_id [{$externalTaskId}].");

                continue;
            }

            $activeRaw = $row['active_status'] ?? null;
            if ($activeRaw !== null && ! $this->isCoercibleActiveStatus($activeRaw)) {
                $this->recordResult($sourceFile, 'warning', 'tasks.active_status_unrecognised', $rowNumber, 'active_status', (string) $activeRaw, "Unrecognised active_status value [{$activeRaw}]; defaulting to inactive.");
            }

            // Dedup on external_task_id: strict (DBML unique NOT NULL).
            // Second occurrence is an error, not a coerced warning.
            if (isset($seenExternalIds[$externalTaskId])) {
                $this->recordResult($sourceFile, 'error', 'tasks.duplicate_external_task_id_in_source', $rowNumber, 'task_id', $externalTaskId, "Row skipped: task_id [{$externalTaskId}] already used by an earlier row in this import.");

                continue;
            }
            $seenExternalIds[$externalTaskId] = true;

            // Dedup on task_candidate_key when present (nullable + partial unique in DB).
            // Second occurrence in this import is imported with candidate_key=null + warning,
            // matching the Industries/Occupations external-id coercion shape.
            $candidateKey = $this->stringOrNull($row['task_candidate_key'] ?? null);
            if ($candidateKey !== null) {
                if (isset($seenCandidateKeys[$candidateKey])) {
                    $this->recordResult($sourceFile, 'warning', 'tasks.duplicate_candidate_key_in_source', $rowNumber, 'task_candidate_key', $candidateKey, "task_candidate_key [{$candidateKey}] already used by an earlier row in this import; importing row without task_candidate_key.");
                    $row['task_candidate_key'] = null;
                } else {
                    $seenCandidateKeys[$candidateKey] = true;
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
            Task::query()->updateOrCreate(
                ['external_task_id' => (string) $row['task_id']],
                [
                    'task_name' => (string) $row['task_name'],
                    'task_title' => $this->stringOrNull($row['task_title'] ?? null),
                    'document_type' => $this->stringOrNull($row['document_type'] ?? null),
                    'trade_industry' => $this->stringOrNull($row['trade_industry'] ?? null),
                    'task_group' => $this->stringOrNull($row['task_group'] ?? null),
                    'task_sub_group' => $this->stringOrNull($row['task_sub_group'] ?? null),
                    'task_leaf' => $this->stringOrNull($row['task_leaf'] ?? null),
                    'task_candidate_key' => $this->stringOrNull($row['task_candidate_key'] ?? null),
                    'active_status' => $this->coerceActiveStatus($row['active_status'] ?? null),
                ],
            );

            $written++;
        }

        return $written;
    }
}
