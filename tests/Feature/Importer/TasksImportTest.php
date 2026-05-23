<?php

declare(strict_types=1);

namespace Tests\Feature\Importer;

use App\Domain\Shared\Importer\Models\ImportValidationResult;
use App\Domain\Shared\Importer\Services\ImportRunner;
use App\Domain\Whs\Importer\Tabs\TasksTabImporter;
use App\Domain\Whs\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class TasksImportTest extends TestCase
{
    private const SRC_001_PATH = __DIR__.'/../../../.localdoc/OpsFortress_Central_Occupation_Industry_Source_Pack_v4_schema_locked.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Importer tests require a PostgreSQL connection.');
        }
    }

    public function test_clean_import_writes_tasks_with_no_dedup_warnings(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearTasks();

        $runner = app(ImportRunner::class);
        $batch = $runner->run(self::SRC_001_PATH, [app(TasksTabImporter::class)]);

        $summary = $batch->summary;

        // RAW_All_Task_Register sample carries 4 data rows, all with distinct
        // task_id values and distinct task_candidate_key values. So unlike
        // Industries/Occupations, no dedup warnings are expected.
        $this->assertSame(4, $summary['rows_read']);
        $this->assertSame(4, $summary['rows_written']);
        $this->assertSame(0, $summary['errors']);
        $this->assertSame(0, $summary['warnings']);
        $this->assertSame('completed', $batch->status);

        // Anchor: one specific row should be present with its mapped fields populated.
        $task = Task::query()->where('external_task_id', 'TASK_CCB_001')->first();
        $this->assertNotNull($task);
        $this->assertSame('Cutting concrete blocks', $task->task_name);
        $this->assertSame('Cutting concrete blocks', $task->task_title);
        $this->assertSame('SWMS', $task->document_type);
        $this->assertSame('Construction', $task->task_group);
        $this->assertSame('Masonry', $task->task_sub_group);
        $this->assertSame('Cutting concrete blocks', $task->task_leaf);
        $this->assertSame('construction|masonry|cutting_concrete_blocks', $task->task_candidate_key);
        $this->assertTrue($task->active_status);
    }

    public function test_re_running_the_same_import_is_idempotent(): void
    {
        $this->skipIfSourcePackMissing();

        $this->clearTasks();

        $runner = app(ImportRunner::class);
        $runner->run(self::SRC_001_PATH, [app(TasksTabImporter::class)]);
        $countAfterFirst = Task::query()->count();

        $secondBatch = $runner->run(self::SRC_001_PATH, [app(TasksTabImporter::class)]);

        $this->assertSame($countAfterFirst, Task::query()->count());
        $this->assertSame($countAfterFirst, $secondBatch->summary['rows_written']);
        $this->assertSame(0, $secondBatch->summary['errors']);
    }

    public function test_row_missing_task_id_produces_error_and_is_skipped(): void
    {
        $this->clearTasks();

        $tmpPath = $this->writeFixtureWorkbook([
            ['task_id', 'task_name', 'task_title', 'document_type', 'trade_industry', 'task_group', 'task_sub_group', 'task_leaf', 'task_candidate_key', 'active_status'],
            ['TASK-OK-1', 'OK task',       'OK task',       'SWMS', 'Construction', 'Construction', 'General', 'OK',        'construction|ok',   'Active'],
            [null,        'No task_id',    'No task_id',    'SWMS', 'Construction', 'Construction', 'General', 'No id',     'construction|noid', 'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(TasksTabImporter::class)]);

        $this->assertSame(2, $batch->summary['rows_read']);
        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(1, $batch->summary['errors']);
        $this->assertSame('completed_with_errors', $batch->status);

        $error = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'error')
            ->first();
        $this->assertNotNull($error);
        $this->assertSame('business:tasks.external_task_id_missing', $error->rule_code);
        $this->assertSame('task_id', $error->source_column_name);
        $this->assertSame('tasks', $error->target_table);

        $this->assertSame(1, Task::query()->count());

        unlink($tmpPath);
    }

    public function test_row_missing_task_name_produces_error_and_is_skipped(): void
    {
        $this->clearTasks();

        $tmpPath = $this->writeFixtureWorkbook([
            ['task_id', 'task_name', 'task_title', 'document_type', 'trade_industry', 'task_group', 'task_sub_group', 'task_leaf', 'task_candidate_key', 'active_status'],
            ['TASK-NN-1', null,        'No name',    'SWMS', 'Construction', 'Construction', 'General', 'No name',   'construction|noname', 'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(TasksTabImporter::class)]);

        $this->assertSame(1, $batch->summary['rows_read']);
        $this->assertSame(0, $batch->summary['rows_written']);
        $this->assertSame(1, $batch->summary['errors']);

        $error = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'error')
            ->first();
        $this->assertNotNull($error);
        $this->assertSame('business:tasks.task_name_missing', $error->rule_code);
        $this->assertSame('task_name', $error->source_column_name);
        $this->assertSame(0, Task::query()->count());

        unlink($tmpPath);
    }

    public function test_duplicate_task_id_in_source_is_a_hard_error_not_a_warning(): void
    {
        // Unlike Industries/Occupations where a duplicate external_id is coerced to
        // null + warning (because external_id is advisory there), tasks.external_task_id
        // is the upsert key + DBML-required identity, so a duplicate is rejected outright.
        $this->clearTasks();

        $tmpPath = $this->writeFixtureWorkbook([
            ['task_id', 'task_name', 'task_title', 'document_type', 'trade_industry', 'task_group', 'task_sub_group', 'task_leaf', 'task_candidate_key', 'active_status'],
            ['TASK-DUP-1', 'First',  'First',  'SWMS', 'Construction', 'Construction', 'General', 'First',  'construction|first',  'Active'],
            ['TASK-DUP-1', 'Second', 'Second', 'SWMS', 'Construction', 'Construction', 'General', 'Second', 'construction|second', 'Active'],
        ]);

        $runner = app(ImportRunner::class);
        $batch = $runner->run($tmpPath, [app(TasksTabImporter::class)]);

        $this->assertSame(2, $batch->summary['rows_read']);
        $this->assertSame(1, $batch->summary['rows_written']);
        $this->assertSame(1, $batch->summary['errors']);
        $this->assertSame(0, $batch->summary['warnings']);

        $error = ImportValidationResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('severity', 'error')
            ->where('rule_code', 'dup:tasks.external_task_id_in_source')
            ->first();
        $this->assertNotNull($error);

        // First row wins.
        $task = Task::query()->where('external_task_id', 'TASK-DUP-1')->first();
        $this->assertNotNull($task);
        $this->assertSame('First', $task->task_name);

        unlink($tmpPath);
    }

    private function clearTasks(): void
    {
        // Clear children first to satisfy FKs; seeded rows reference tasks via
        // task_occupation_access, task_industry_access, swms_versions
        // (and downstream), prestart_questions, workplace_task_settings.
        DB::statement('TRUNCATE TABLE audit_events, evidence_files, signatures RESTART IDENTITY CASCADE');
        DB::table('alerts')->delete();
        DB::table('workplace_task_settings')->delete();
        DB::table('prestart_responses')->delete();
        DB::table('prestart_submissions')->delete();
        DB::table('prestart_questions')->delete();
        DB::table('swms_step_events')->delete();
        DB::table('swms_activity_steps')->delete();
        DB::table('worker_task_sessions')->delete();
        DB::table('swms_versions')->delete();
        DB::table('task_occupation_access')->delete();
        DB::table('task_industry_access')->delete();
        DB::table('tasks')->delete();
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
        $sheet->setTitle('RAW_All_Task_Register');

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
