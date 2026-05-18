<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Importer\Contracts\TabImporter;
use App\Domain\Shared\Importer\Services\ImportRunner;
use App\Domain\Whs\Importer\Tabs\IndustriesTabImporter;
use App\Domain\Whs\Importer\Tabs\OccupationsTabImporter;
use Illuminate\Console\Command;

/**
 * Allow-list-driven workbook importer.
 *
 * Usage:
 *   php artisan opsf:import path/to/workbook.xlsx
 *   php artisan opsf:import path/to/workbook.xlsx --tab=RAW_All_Industry_Master
 *
 * Slice 1 only wires the IndustriesTabImporter. Subsequent slices add more
 * tab importers behind the same command.
 */
final class ImportCommand extends Command
{
    protected $signature = 'opsf:import
        {path : Path to the source workbook (.xlsx/.xlsm)}
        {--tab=* : Limit to specific sheet names (default = all wired tabs)}';

    protected $description = 'Import an allow-listed OpsFortress source workbook into PostgreSQL.';

    public function handle(ImportRunner $runner): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("Source file not found: {$path}");

            return self::FAILURE;
        }

        $tabFilter = (array) $this->option('tab');
        $tabImporters = $this->resolveTabImporters($tabFilter);

        if ($tabImporters === []) {
            $this->error('No tab importers selected. Available tabs: '.implode(', ', $this->availableTabNames()));

            return self::FAILURE;
        }

        $this->line("Importing {$path}");
        $this->line('Tabs: '.implode(', ', array_map(fn (TabImporter $t): string => $t->sheetName(), $tabImporters)));

        $batch = $runner->run($path, $tabImporters);

        $summary = $batch->summary ?? [];
        $this->line('---');
        $this->line("Batch:        {$batch->id}");
        $this->line("Status:       {$batch->status}");
        $this->line('Rows read:    '.($summary['rows_read'] ?? 0));
        $this->line('Rows written: '.($summary['rows_written'] ?? 0));
        $this->line('Errors:       '.($summary['errors'] ?? 0));
        $this->line('Warnings:     '.($summary['warnings'] ?? 0));

        return ($summary['errors'] ?? 0) === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<string>  $tabFilter
     * @return list<TabImporter>
     */
    private function resolveTabImporters(array $tabFilter): array
    {
        $all = $this->allWiredTabImporters();

        if ($tabFilter === []) {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (TabImporter $t): bool => in_array($t->sheetName(), $tabFilter, true),
        ));
    }

    /**
     * @return list<TabImporter>
     */
    private function allWiredTabImporters(): array
    {
        return [
            app(IndustriesTabImporter::class),
            app(OccupationsTabImporter::class),
        ];
    }

    /**
     * @return list<string>
     */
    private function availableTabNames(): array
    {
        return array_map(
            static fn (TabImporter $t): string => $t->sheetName(),
            $this->allWiredTabImporters(),
        );
    }
}
