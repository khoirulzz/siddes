<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelReaderType;

class SpreadsheetImportHelper
{
    /**
     * @var array<int, string>
     */
    private const CSV_DELIMITER_CANDIDATES = [
        ';',
        ',',
        "\t",
        '|',
    ];

    /**
     * @var array<int, string>
     */
    private const CSV_EXTENSIONS = [
        'csv',
        'txt',
    ];

    public static function detectCsvDelimiter(UploadedFile $file): string
    {
        if (! self::isCsv($file)) {
            return ',';
        }

        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            return ',';
        }

        $handle = @fopen($path, 'rb');
        if (! is_resource($handle)) {
            return ',';
        }

        $scores = array_fill_keys(self::CSV_DELIMITER_CANDIDATES, 0);
        $rowCount = 0;

        while (($line = fgets($handle)) !== false && $rowCount < 8) {
            $rowCount++;

            if ($rowCount === 1) {
                $line = self::stripUtf8Bom($line);
            }

            if (trim($line) === '') {
                continue;
            }

            foreach (self::CSV_DELIMITER_CANDIDATES as $delimiter) {
                $columns = str_getcsv($line, $delimiter);
                if (count($columns) > 1) {
                    $scores[$delimiter] += count($columns);
                }
            }
        }

        fclose($handle);
        arsort($scores);

        $bestDelimiter = array_key_first($scores);
        if (! is_string($bestDelimiter) || ($scores[$bestDelimiter] ?? 0) === 0) {
            return ',';
        }

        return $bestDelimiter;
    }

    public static function resolveReaderType(UploadedFile $file): ?string
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'csv', 'txt' => ExcelReaderType::CSV,
            'xlsx' => ExcelReaderType::XLSX,
            'xls' => ExcelReaderType::XLS,
            default => null,
        };
    }

    private static function isCsv(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::CSV_EXTENSIONS, true);
    }

    private static function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }
}

