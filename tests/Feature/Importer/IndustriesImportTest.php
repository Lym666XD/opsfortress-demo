<?php

declare(strict_types=1);

namespace Tests\Feature\Importer;

use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Domain\Shared\Importer\Models\ImportValidationResult;
use App\Domain\Shared\Importer\Services\ImportRunner;
use App\Domain\Whs\Importer\Tabs\IndustriesTabImporter;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class IndustriesImportTest extends TestCase
{
    private const SRC_001_PATH = __DIR__.'/../../../.localdoc/OpsFortress_Central_Occupation_Industry_Source_Pack_v4_schema_locked.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Importer tests require a PostgreSQL connection.');
        }
    }

    public function test_clean_import_writes_industries_and_reports_real_source_quirks(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearIndustries();

        $runner = app(ImportRunner::class);
        $batch = $runner->run(self::SRC_001_PATH, [app(IndustriesTabImporter::class)]);

        $summary = $batch->summary;

        // RAW_All_Industry_Master sample currently carries 4 data rows.
        $this->assertSame(4, $summary['rows_read']);

        // Two source rows share industry_candidate_key
        // (R2 + R3 both 'construction|masonry|brick_block_laying'); first wins.
        // Two source rows share industry_record_id IND-001 across DIFFERENT
        // candidate_keys (R2 + R5); first keeps the external_id, second gets null.
        // Net: 3 industries written, 2 warnings, 0 errors.
        $this->assertSame(3, $summary['rows_written']);
        $this->assertSame(0, $summary['errors']);
        $this->assertSame(2, $summary['warnings']);

        $this->assertSame(3, Industry::query()->count());
        $this->assertSame('completed', $batch->status);

        // The duplicate-external-id row should still be in the DB without an external_industry_id.
        $orphanCount = Industry::query()->whereNull('external_industry_id')->count();
        $this->assertGreaterThanOrEqual(1, $orphanCount);
    }

    public function test_re_running_the_same_import_is_idempotent(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearIndustries();

        $runner = app(ImportRunner::class);
        $runner->run(self::SRC_001_PATH, [app(IndustriesTabImporter::class)]);
        $countAfterFirst = Industry::query()->count();

        $secondBatch = $runner->run(self::SRC_001_PATH, [app(IndustriesTabImporter::class)]);

        $this->assertSame($countAfterFirst, Industry::query()->count());
        $this->assertSame($countAfterFirst, $secondBatch->summary['rows_written']);
        $this->assertSame(0, $secondBatch->summary['errors']);
    }

    public function test_row_missing_candidate_key_produces_validation_error_and_is_skipped(): void
    {
        $this->clearIndustries();

        $tmpPath = $this->writeFixtureWorkbook([
            ['industry_record_id', 'industry_group', 'industry_sub_group', 'industry_leaf', 'industry_candidate_key', 'active_status'],
            ['IND-OK-1',   'Construction', 'General',    'Site work',          'construction|site_work',         'Active'],
            ['IND-BAD-1',  'Construction', 'General',    'Missing key row',    null,                              'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(IndustriesTabImporter::class)]);

        $this->assertSame(2, $batch->summary['rows_read']);
        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(1, $batch->summary['errors']);
        $this->assertSame('completed_with_errors', $batch->status);

        $error = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'error')
            ->first();
        $this->assertNotNull($error);
        $this->assertSame('business:industries.candidate_key_missing', $error->rule_code);
        $this->assertSame('industry_candidate_key', $error->source_column_name);
        $this->assertSame('industries', $error->target_table);
        $this->assertSame(3, $error->source_row_number); // header + 1 ok + 1 bad

        $this->assertSame(1, Industry::query()->count());
        $this->assertNotNull(Industry::query()->where('industry_candidate_key', 'construction|site_work')->first());

        unlink($tmpPath);
    }

    public function test_unknown_active_status_value_imports_as_inactive_with_warning(): void
    {
        $this->clearIndustries();

        $tmpPath = $this->writeFixtureWorkbook([
            ['industry_record_id', 'industry_group', 'industry_sub_group', 'industry_leaf', 'industry_candidate_key', 'active_status'],
            ['IND-W-1',  'Construction', 'General',   'Weird status',     'construction|weird_status',   'maybe-soon'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(IndustriesTabImporter::class)]);

        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(0, $batch->summary['errors']);
        $this->assertSame(1, $batch->summary['warnings']);

        $industry = Industry::query()->where('industry_candidate_key', 'construction|weird_status')->first();
        $this->assertNotNull($industry);
        $this->assertFalse($industry->active_status);

        $warning = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'warning')
            ->first();
        $this->assertNotNull($warning);
        $this->assertSame('business:industries.active_status_unrecognised', $warning->rule_code);
        $this->assertSame('maybe-soon', $warning->raw_value);

        unlink($tmpPath);
    }

    public function test_import_batch_lifecycle_writes_source_file_and_summary(): void
    {
        $this->clearIndustries();

        $tmpPath = $this->writeFixtureWorkbook([
            ['industry_record_id', 'industry_group', 'industry_sub_group', 'industry_leaf', 'industry_candidate_key', 'active_status'],
            ['IND-LC-1', 'Construction', 'General', 'Lifecycle check', 'construction|lifecycle', 'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(IndustriesTabImporter::class)]);

        $this->assertSame('completed', $batch->status);
        $this->assertNotNull($batch->started_at);
        $this->assertNotNull($batch->completed_at);
        $this->assertArrayHasKey('tabs', $batch->summary);
        $this->assertArrayHasKey('RAW_All_Industry_Master', $batch->summary['tabs']);

        $sourceFile = $batch->sourceFiles()->first();
        $this->assertNotNull($sourceFile);
        $this->assertSame('completed', $sourceFile->status);
        $this->assertSame(64, strlen($sourceFile->file_hash)); // sha256 hex length

        // Idempotent re-run with same hash should pass — only protected by unique
        // (import_batch_id, file_hash), not by file_hash alone.
        $batch2 = $runner->run($tmpPath, [app(IndustriesTabImporter::class)]);
        $sourceFile2 = $batch2->sourceFiles()->first();
        $this->assertSame($sourceFile->file_hash, $sourceFile2->file_hash);
        $this->assertNotSame($batch->id, $batch2->id);

        unlink($tmpPath);
    }

    private function clearIndustries(): void
    {
        // Clear children first to satisfy FKs; seeded rows reference industries
        // via business_industries and (in M17 slice 4) task_industry_access.
        DB::table('task_industry_access')->delete();
        DB::table('business_industries')->delete();
        DB::table('industries')->delete();
    }

    private function skipIfSourcePackMissing(): void
    {
        if (! is_file(self::SRC_001_PATH)) {
            $this->markTestSkipped('SRC-001 source pack not present in .localdoc/.');
        }
    }

    /**
     * @param  list<array<int, mixed>>  $rows  first row is header, rest are data
     */
    private function writeFixtureWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RAW_All_Industry_Master');

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'opsf_import_test_').'.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);

        return $tmpPath;
    }
}
