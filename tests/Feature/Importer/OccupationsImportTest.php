<?php

declare(strict_types=1);

namespace Tests\Feature\Importer;

use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\Shared\Importer\Models\ImportValidationResult;
use App\Domain\Shared\Importer\Services\ImportRunner;
use App\Domain\Whs\Importer\Tabs\OccupationsTabImporter;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class OccupationsImportTest extends TestCase
{
    private const SRC_001_PATH = __DIR__.'/../../../.localdoc/OpsFortress_Central_Occupation_Industry_Source_Pack_v4_schema_locked.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Importer tests require a PostgreSQL connection.');
        }
    }

    public function test_clean_import_writes_occupations_and_reports_real_source_quirks(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearOccupations();

        $runner = app(ImportRunner::class);
        $batch = $runner->run(self::SRC_001_PATH, [app(OccupationsTabImporter::class)]);

        $summary = $batch->summary;

        // RAW_All_Occupation_Master sample currently carries 8 data rows.
        $this->assertSame(8, $summary['rows_read']);

        // All 8 source rows have distinct candidate_keys -> 8 occupations written.
        // BUT occupation_record_id OCC-001 appears twice (R2 'bricklayer' + R7 'block_layer')
        // and OCC-002 appears twice (R3 'labourer' + R8 'forklift_operator').
        // First wins the external_id; later duplicates get external_occupation_id=null
        // + warning. Net: 8 written, 0 errors, 2 warnings.
        $this->assertSame(8, $summary['rows_written']);
        $this->assertSame(0, $summary['errors']);
        $this->assertSame(2, $summary['warnings']);

        $this->assertSame(8, Occupation::query()->count());
        $this->assertSame('completed', $batch->status);

        // Both duplicate-external-id rows are still present without external_occupation_id.
        $orphanCount = Occupation::query()->whereNull('external_occupation_id')->count();
        $this->assertSame(2, $orphanCount);

        // Specific anchors from the source: first OCC-001 row keeps external_id,
        // the bricklayer leaf is intact.
        $bricklayer = Occupation::query()
            ->where('occupation_candidate_key', 'construction|brick_block|bricklayer')
            ->first();
        $this->assertNotNull($bricklayer);
        $this->assertSame('OCC-001', $bricklayer->external_occupation_id);
        $this->assertSame('Bricklayer', $bricklayer->occupation_leaf);
        $this->assertTrue($bricklayer->active_status);
    }

    public function test_re_running_the_same_import_is_idempotent(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearOccupations();

        $runner = app(ImportRunner::class);
        $runner->run(self::SRC_001_PATH, [app(OccupationsTabImporter::class)]);
        $countAfterFirst = Occupation::query()->count();

        $secondBatch = $runner->run(self::SRC_001_PATH, [app(OccupationsTabImporter::class)]);

        $this->assertSame($countAfterFirst, Occupation::query()->count());
        $this->assertSame($countAfterFirst, $secondBatch->summary['rows_written']);
        $this->assertSame(0, $secondBatch->summary['errors']);
    }

    public function test_row_missing_candidate_key_produces_validation_error_and_is_skipped(): void
    {
        $this->clearOccupations();

        $tmpPath = $this->writeFixtureWorkbook([
            ['occupation_record_id', 'occupation_group', 'occupation_sub_group', 'occupation_leaf', 'occupation_candidate_key', 'active_status'],
            ['OCC-OK-1',  'Construction trades', 'Brick and block', 'Bricklayer',    'construction|brick|bricklayer', 'Active'],
            ['OCC-BAD-1', 'Construction trades', 'Brick and block', 'No key row',    null,                            'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(OccupationsTabImporter::class)]);

        $this->assertSame(2, $batch->summary['rows_read']);
        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(1, $batch->summary['errors']);
        $this->assertSame('completed_with_errors', $batch->status);

        $error = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'error')
            ->first();
        $this->assertNotNull($error);
        $this->assertSame('business:occupations.candidate_key_missing', $error->rule_code);
        $this->assertSame('occupation_candidate_key', $error->source_column_name);
        $this->assertSame('occupations', $error->target_table);
        $this->assertSame(3, $error->source_row_number); // header + 1 ok + 1 bad

        $this->assertSame(1, Occupation::query()->count());
        $this->assertNotNull(Occupation::query()->where('occupation_candidate_key', 'construction|brick|bricklayer')->first());

        unlink($tmpPath);
    }

    public function test_unknown_active_status_value_imports_as_inactive_with_warning(): void
    {
        $this->clearOccupations();

        $tmpPath = $this->writeFixtureWorkbook([
            ['occupation_record_id', 'occupation_group', 'occupation_sub_group', 'occupation_leaf', 'occupation_candidate_key', 'active_status'],
            ['OCC-W-1', 'Construction trades', 'Brick and block', 'Weird status', 'construction|brick|weird', 'maybe-soon'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(OccupationsTabImporter::class)]);

        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(0, $batch->summary['errors']);
        $this->assertSame(1, $batch->summary['warnings']);

        $occupation = Occupation::query()->where('occupation_candidate_key', 'construction|brick|weird')->first();
        $this->assertNotNull($occupation);
        $this->assertFalse($occupation->active_status);

        $warning = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'warning')
            ->first();
        $this->assertNotNull($warning);
        $this->assertSame('business:occupations.active_status_unrecognised', $warning->rule_code);
        $this->assertSame('maybe-soon', $warning->raw_value);

        unlink($tmpPath);
    }

    private function clearOccupations(): void
    {
        // Clear children first to satisfy FKs; seeded rows reference occupations
        // via task_occupation_access and seeded worker user occupations.
        DB::table('user_occupations')->delete();
        DB::table('task_occupation_access')->delete();
        DB::table('occupations')->delete();
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
        $sheet->setTitle('RAW_All_Occupation_Master');

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
