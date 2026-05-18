<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Services;

use App\Domain\Shared\Importer\Contracts\TabImporter;
use App\Domain\Shared\Importer\Models\ImportBatch;
use App\Domain\Shared\Importer\Models\ImportSourceFile;
use App\Domain\Shared\Importer\Models\ImportValidationResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Orchestrates a single import run: takes a source file path + a list of
 * TabImporter implementations, opens an ImportBatch, parses each tab,
 * validates, commits, and finalises the batch with a summary.
 *
 * Allow-list discipline: the runner only reads sheets that have a
 * corresponding TabImporter passed in. Other sheets in the workbook
 * (the 65-tab WHSAPP workbook has many we don't import) are ignored.
 */
final class ImportRunner
{
    public function __construct(private readonly WorkbookReader $reader) {}

    /**
     * @param  list<TabImporter>  $tabImporters
     */
    public function run(
        string $path,
        array $tabImporters,
        string $importType = 'content_workbook',
        ?string $accountId = null,
        ?string $startedByUserId = null,
    ): ImportBatch {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Source file not readable: {$path}");
        }

        $fileHash = hash_file('sha256', $path);
        $sizeBytes = filesize($path) ?: null;
        $originalName = basename($path);

        $batch = ImportBatch::create([
            'account_id' => $accountId,
            'import_type' => $importType,
            'status' => 'running',
            'started_by_user_id' => $startedByUserId,
            'started_at' => now(),
        ]);

        $sourceFile = ImportSourceFile::create([
            'import_batch_id' => $batch->id,
            'original_filename' => $originalName,
            'storage_disk' => null,
            'storage_path' => $path,
            'file_hash' => $fileHash,
            'file_hash_algorithm' => 'sha256',
            'size_bytes' => $sizeBytes,
            'workbook_type' => $importType,
            'status' => 'reading',
        ]);

        $summary = [
            'tabs' => [],
            'rows_read' => 0,
            'rows_written' => 0,
            'errors' => 0,
            'warnings' => 0,
        ];

        try {
            foreach ($tabImporters as $tabImporter) {
                $tabSummary = $this->runTab($tabImporter, $path, $sourceFile);
                $summary['tabs'][$tabImporter->sheetName()] = $tabSummary;
                $summary['rows_read'] += $tabSummary['rows_read'];
                $summary['rows_written'] += $tabSummary['rows_written'];
                $summary['errors'] += $tabSummary['errors'];
                $summary['warnings'] += $tabSummary['warnings'];
            }

            $sourceFile->update(['status' => $summary['errors'] === 0 ? 'completed' : 'completed_with_errors']);
            $batch->update([
                'status' => $summary['errors'] === 0 ? 'completed' : 'completed_with_errors',
                'completed_at' => now(),
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            $sourceFile->update(['status' => 'failed']);
            $batch->update([
                'status' => 'failed',
                'completed_at' => now(),
                'summary' => $summary + ['error_message' => $e->getMessage()],
            ]);

            throw $e;
        }

        return $batch->refresh();
    }

    /**
     * @return array{rows_read:int,rows_written:int,errors:int,warnings:int}
     */
    private function runTab(TabImporter $tabImporter, string $path, ImportSourceFile $sourceFile): array
    {
        $rows = $this->reader->readSheet($path, $tabImporter->sheetName());
        $rowsRead = count($rows);

        $errorCountBefore = $this->countResults($sourceFile, $tabImporter->sheetName(), 'error');
        $warningCountBefore = $this->countResults($sourceFile, $tabImporter->sheetName(), 'warning');

        $validated = $tabImporter->validate($rows, $sourceFile);

        $rowsWritten = DB::transaction(fn (): int => $tabImporter->commit($validated, $sourceFile));

        $errorDelta = $this->countResults($sourceFile, $tabImporter->sheetName(), 'error') - $errorCountBefore;
        $warningDelta = $this->countResults($sourceFile, $tabImporter->sheetName(), 'warning') - $warningCountBefore;

        return [
            'rows_read' => $rowsRead,
            'rows_written' => $rowsWritten,
            'errors' => $errorDelta,
            'warnings' => $warningDelta,
        ];
    }

    private function countResults(ImportSourceFile $sourceFile, string $sheetName, string $severity): int
    {
        return ImportValidationResult::query()
            ->where('import_source_file_id', $sourceFile->id)
            ->where('source_sheet_name', $sheetName)
            ->where('severity', $severity)
            ->count();
    }
}
