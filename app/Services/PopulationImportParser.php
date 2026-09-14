<?php

namespace App\Services;

use App\Exceptions\PopulationImportException;
use App\Support\PopulationImportSchema;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PopulationImportParser
{
    public const MAX_ROWS = 10_000;

    private const HEADER_SCAN_LIMIT = 15;

    private const DELIMITERS = [';', ',', "\t", '|'];

    /**
     * @return array{sheet: string, header_row: int, headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new PopulationImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        $signature = @file_get_contents($path, false, null, 0, 8);
        $isXlsx = is_string($signature) && str_starts_with($signature, "PK\x03\x04");
        $isXls = is_string($signature) && str_starts_with($signature, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");

        return ($isXlsx || $isXls)
            ? $this->parseSpreadsheet($file)
            : $this->parseDelimitedFile($file);
    }

    private function parseDelimitedFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new PopulationImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            throw new PopulationImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        if (str_starts_with($contents, "\xFF\xFE")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($contents, "\xFE\xFF")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        } else {
            $encoding = mb_detect_encoding($contents, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';
            if ($encoding !== 'UTF-8') {
                $contents = mb_convert_encoding($contents, 'UTF-8', $encoding);
            }
        }

        $handle = fopen('php://temp', 'w+b');
        if (! is_resource($handle)) {
            throw new PopulationImportException('File tidak dapat diproses sementara. Silakan coba lagi.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $delimiter = $this->detectDelimiter($contents);
        $sourceRows = [];
        $rowNumber = 0;

        while (($values = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rowNumber++;
            $sourceRows[$rowNumber] = $values;

            if ($rowNumber > self::MAX_ROWS + self::HEADER_SCAN_LIMIT + 1) {
                fclose($handle);
                throw new PopulationImportException('File melebihi batas 10.000 baris data. Pecah file menjadi beberapa bagian.');
            }
        }
        fclose($handle);

        [$headerRow, $columnMap] = $this->findArrayHeader($sourceRows);
        $rows = [];
        foreach ($sourceRows as $number => $values) {
            if ($number <= $headerRow) {
                continue;
            }

            $cells = [];
            foreach ($columnMap as $index => $canonical) {
                $cells[$canonical] = [
                    'value' => $values[$index] ?? null,
                    'type' => DataType::TYPE_STRING,
                    'is_date' => false,
                ];
            }

            if (! $this->cellsContainData($cells)) {
                continue;
            }

            $rows[] = ['_row' => $number, '_cells' => $cells];
            if (count($rows) > self::MAX_ROWS) {
                throw new PopulationImportException('File melebihi batas 10.000 baris data. Pecah file menjadi beberapa bagian.');
            }
        }

        return [
            'sheet' => 'Data',
            'header_row' => $headerRow,
            'headers' => array_values($columnMap),
            'rows' => $rows,
        ];
    }

    private function parseSpreadsheet(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new PopulationImportException('File Excel tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $exception) {
            throw new PopulationImportException('File Excel rusak, berpassword, atau format aslinya tidak sesuai ekstensi.', previous: $exception);
        }

        try {
            $worksheets = $spreadsheet->getAllSheets();
            usort($worksheets, static function (Worksheet $left, Worksheet $right): int {
                $leftPreferred = in_array(PopulationImportSchema::normalizeHeader($left->getTitle()), ['data', 'data_kependudukan', 'data_penduduk'], true);
                $rightPreferred = in_array(PopulationImportSchema::normalizeHeader($right->getTitle()), ['data', 'data_kependudukan', 'data_penduduk'], true);

                return (int) $rightPreferred <=> (int) $leftPreferred;
            });

            foreach ($worksheets as $worksheet) {
                $header = $this->findWorksheetHeader($worksheet);
                if ($header === null) {
                    continue;
                }

                [$headerRow, $columnMap] = $header;
                $rows = [];
                $highestRow = $worksheet->getHighestDataRow();
                if ($highestRow - $headerRow > self::MAX_ROWS) {
                    throw new PopulationImportException('File melebihi batas 10.000 baris data. Pecah file menjadi beberapa bagian.');
                }
                for ($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++) {
                    $cells = [];
                    foreach ($columnMap as $columnIndex => $canonical) {
                        $cell = $worksheet->getCell([$columnIndex, $rowNumber]);
                        $cells[$canonical] = [
                            'value' => $cell->getValue(),
                            'type' => $cell->getDataType(),
                            'is_date' => $cell->getDataType() === DataType::TYPE_NUMERIC && ExcelDate::isDateTime($cell),
                        ];
                    }

                    if (! $this->cellsContainData($cells)) {
                        continue;
                    }

                    $rows[] = ['_row' => $rowNumber, '_cells' => $cells];
                    if (count($rows) > self::MAX_ROWS) {
                        throw new PopulationImportException('File melebihi batas 10.000 baris data. Pecah file menjadi beberapa bagian.');
                    }
                }

                return [
                    'sheet' => $worksheet->getTitle(),
                    'header_row' => $headerRow,
                    'headers' => array_values($columnMap),
                    'rows' => $rows,
                ];
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        throw new PopulationImportException('Header data tidak ditemukan. Minimal sertakan kolom no_kk, nik, dan nama_lengkap.');
    }

    /** @return array{0: int, 1: array<int, string>} */
    private function findArrayHeader(array $rows): array
    {
        $bestRow = null;
        $bestMap = [];
        foreach (array_slice($rows, 0, self::HEADER_SCAN_LIMIT, true) as $rowNumber => $values) {
            $map = $this->mapHeaders($values);
            if (count($map) > count($bestMap)) {
                $bestRow = (int) $rowNumber;
                $bestMap = $map;
            }
        }

        $missing = PopulationImportSchema::missingMinimumHeaders(array_values($bestMap));
        if ($bestRow === null || $missing !== []) {
            throw new PopulationImportException('Header data tidak lengkap. Kolom wajib yang belum dikenali: '.implode(', ', $missing ?: PopulationImportSchema::MINIMUM_HEADERS).'.');
        }
        $duplicates = $this->duplicateCanonicalHeaders($rows[$bestRow] ?? []);
        if ($duplicates !== []) {
            throw new PopulationImportException('Header ganda terdeteksi untuk kolom: '.implode(', ', $duplicates).'. Hapus kolom duplikat lalu coba lagi.');
        }

        return [$bestRow, $bestMap];
    }

    /** @return array{0: int, 1: array<int, string>}|null */
    private function findWorksheetHeader(Worksheet $worksheet): ?array
    {
        $highestColumn = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
        $scanTo = min($worksheet->getHighestDataRow(), self::HEADER_SCAN_LIMIT);
        $bestRow = null;
        $bestMap = [];

        for ($row = 1; $row <= $scanTo; $row++) {
            $values = [];
            for ($column = 1; $column <= $highestColumn; $column++) {
                $values[$column] = $worksheet->getCell([$column, $row])->getValue();
            }
            $map = $this->mapHeaders($values);
            if (count($map) > count($bestMap)) {
                $bestRow = $row;
                $bestMap = $map;
            }
        }

        if ($bestRow === null || PopulationImportSchema::missingMinimumHeaders(array_values($bestMap)) !== []) {
            return null;
        }

        $headerValues = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headerValues[] = $worksheet->getCell([$column, $bestRow])->getValue();
        }
        $duplicates = $this->duplicateCanonicalHeaders($headerValues);
        if ($duplicates !== []) {
            throw new PopulationImportException('Header ganda terdeteksi untuk kolom: '.implode(', ', $duplicates).'. Hapus kolom duplikat lalu coba lagi.');
        }

        return [$bestRow, $bestMap];
    }

    /** @return array<int, string> */
    private function mapHeaders(array $values): array
    {
        $map = [];
        $seen = [];
        foreach ($values as $index => $value) {
            $canonical = PopulationImportSchema::canonicalHeader($value);
            if ($canonical === null || isset($seen[$canonical])) {
                continue;
            }

            $map[(int) $index] = $canonical;
            $seen[$canonical] = true;
        }

        return $map;
    }

    /** @return array<int, string> */
    private function duplicateCanonicalHeaders(array $values): array
    {
        $counts = [];
        foreach ($values as $value) {
            $canonical = PopulationImportSchema::canonicalHeader($value);
            if ($canonical !== null) {
                $counts[$canonical] = ($counts[$canonical] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, fn (int $count): bool => $count > 1));
    }

    private function cellsContainData(array $cells): bool
    {
        foreach ($cells as $cell) {
            $value = $cell['value'] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function detectDelimiter(string $contents): string
    {
        $scores = array_fill_keys(self::DELIMITERS, 0);
        $lines = preg_split('/\R/u', $contents, 9) ?: [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            foreach (self::DELIMITERS as $delimiter) {
                $columns = str_getcsv($line, $delimiter, '"', '\\');
                if (count($columns) > 1) {
                    $scores[$delimiter] += count($columns);
                }
            }
        }

        arsort($scores);
        $best = array_key_first($scores);

        return is_string($best) && $scores[$best] > 0 ? $best : ',';
    }
}
