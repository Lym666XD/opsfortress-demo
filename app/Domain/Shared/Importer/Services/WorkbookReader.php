<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

/**
 * Thin phpspreadsheet wrapper. Reads one sheet of an XLSX/XLSM workbook as
 * an array of associative rows keyed by header name. Trims string values
 * and skips fully-empty rows.
 *
 * Schema-locked workbooks (per OPSF README_Staff_Copy_Instructions) require
 * the first row to be the header row, in canonical order.
 */
final class WorkbookReader
{
    /**
     * @return list<array<string, mixed>>
     */
    public function readSheet(string $path, string $sheetName): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Workbook not found or unreadable: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $spreadsheet = $reader->load($path);

        return $this->extractRows($spreadsheet, $sheetName);
    }

    /**
     * Return the list of sheet names in a workbook without parsing cell data.
     *
     * @return list<string>
     */
    public function listSheets(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Workbook not found or unreadable: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->listWorksheetNames($path);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractRows(Spreadsheet $spreadsheet, string $sheetName): array
    {
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            throw new RuntimeException("Sheet [{$sheetName}] not found in workbook.");
        }

        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return [];
        }

        $headers = array_map(
            static fn ($value): string => is_string($value) ? trim($value) : (string) $value,
            $rows[0],
        );

        $out = [];

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $assoc = [];
            $anyValue = false;

            foreach ($headers as $col => $header) {
                if ($header === '') {
                    continue;
                }

                $value = $row[$col] ?? null;

                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        $value = null;
                    }
                }

                if ($value !== null) {
                    $anyValue = true;
                }

                $assoc[$header] = $value;
            }

            if ($anyValue) {
                $out[] = $assoc;
            }
        }

        return $out;
    }
}
