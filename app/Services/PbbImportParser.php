<?php

namespace App\Services;

use App\Exceptions\PbbImportException;
use App\Support\PbbImportSchema;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PbbImportParser
{
    public const MAX_ROWS = 10_000;

    private const HEADER_SCAN_LIMIT = 15;

    private const CHUNK_ROWS = 500;

    private const MAX_COLUMNS = 128;

    private const DELIMITERS = [';', ',', "\t", '|'];

    /**
     * @return array{sheet: string, header_row: int, headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new PbbImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
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
            throw new PbbImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            throw new PbbImportException('File tidak dapat dibaca. Silakan unggah ulang file tersebut.');
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
            throw new PbbImportException('File tidak dapat diproses sementara. Silakan coba lagi.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $delimiter = $this->detectDelimiter($contents);
        $sourceRows = [];
        $rowNumber = 0;

        while (($values = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rowNumber++;
            if ($rowNumber % 100 === 0) {
                PbbImportMemory::check();
            }
            if (count($values) > self::MAX_COLUMNS) {
                fclose($handle);
                throw new PbbImportException('File memiliki terlalu banyak kolom. Gunakan kolom yang tersedia pada template.');
            }
            $sourceRows[$rowNumber] = $values;

            if ($rowNumber > self::MAX_ROWS + self::HEADER_SCAN_LIMIT + 1) {
                fclose($handle);
                $formattedMax = number_format(self::MAX_ROWS, 0, ',', '.');
                throw new PbbImportException("File melebihi batas {$formattedMax} baris data. Pecah file menjadi beberapa bagian.");
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
                $formattedMax = number_format(self::MAX_ROWS, 0, ',', '.');
                throw new PbbImportException("File melebihi batas {$formattedMax} baris data. Pecah file menjadi beberapa bagian.");
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
            throw new PbbImportException('File Excel tidak dapat dibaca. Silakan unggah ulang file tersebut.');
        }

        try {
            $this->checkArchiveSize($path);
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(false);
            $reader->setReadEmptyCells(false);
            $worksheetTargets = $reader instanceof XlsxReader ? $this->xlsxWorksheetTargets($path) : [];
            $worksheets = $reader instanceof XlsxReader
                ? array_map(static fn (string $title): array => ['worksheetName' => $title], array_keys($worksheetTargets))
                : $reader->listWorksheetInfo($path);
            if (count($worksheets) === 0) {
                throw new PbbImportException('Sheet Excel tidak ditemukan. Simpan ulang file sebagai XLSX atau XLS standar.');
            }
            if (count($worksheets) > 8) {
                throw new PbbImportException('File memiliki terlalu banyak sheet. Salin sheet Data ke workbook terpisah (maksimal 8 sheet).');
            }
            usort($worksheets, static function (array $left, array $right): int {
                $leftPreferred = in_array(PbbImportSchema::normalizeHeader($left['worksheetName']), ['data', 'data_pbb', 'data_pajak'], true);
                $rightPreferred = in_array(PbbImportSchema::normalizeHeader($right['worksheetName']), ['data', 'data_pbb', 'data_pajak'], true);

                return (int) $rightPreferred <=> (int) $leftPreferred;
            });

            foreach ($worksheets as $info) {
                PbbImportMemory::check();
                $sheetName = $info['worksheetName'];
                $reader->setLoadSheetsOnly([$sheetName]);
                $reader->setReadFilter($this->readFilter(1, self::HEADER_SCAN_LIMIT));
                $spreadsheet = $reader->load($path);
                try {
                    $header = $this->findWorksheetHeader($spreadsheet->getSheetByName($sheetName));
                } finally {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                    gc_collect_cycles();
                }
                if ($header === null) {
                    continue;
                }

                [$headerRow, $columnMap] = $header;
                $rows = [];
                if (isset($info['totalColumns']) && (int) $info['totalColumns'] > self::MAX_COLUMNS) {
                    throw new PbbImportException('Area sheet terlalu lebar. Hapus kolom tambahan di luar template sebelum mengunggah ulang.');
                }
                $chunkStarts = $reader instanceof XlsxReader
                    ? $this->xlsxContentChunkStarts($path, $worksheetTargets[$sheetName], $headerRow)
                    : $this->legacyChunkStarts((int) $info['totalRows'], $headerRow);

                foreach ($chunkStarts as $start) {
                    PbbImportMemory::check();
                    $end = $start + self::CHUNK_ROWS - 1;
                    $reader->setReadFilter($this->readFilter($start, $end, array_keys($columnMap)));
                    $spreadsheet = $reader->load($path);
                    try {
                        $worksheet = $spreadsheet->getSheetByName($sheetName);
                        for ($rowNumber = $start; $rowNumber <= $end; $rowNumber++) {
                            if ($rowNumber <= $headerRow) {
                                continue;
                            }
                            $cells = [];
                            foreach ($columnMap as $columnIndex => $canonical) {
                                // getCell() creates missing cells. Never materialize blank template areas.
                                if (! $worksheet->cellExists([$columnIndex, $rowNumber])) {
                                    continue;
                                }
                                $cell = $worksheet->getCell([$columnIndex, $rowNumber]);
                                $cells[$canonical] = [
                                    'value' => $cell->getValue(),
                                    'type' => $cell->getDataType(),
                                    'is_date' => $cell->getDataType() === DataType::TYPE_NUMERIC && ExcelDate::isDateTime($cell),
                                ];
                            }

                            if ($this->cellsContainData($cells)) {
                                $rows[] = ['_row' => $rowNumber, '_cells' => $cells];
                            }
                        }
                    } finally {
                        $spreadsheet->disconnectWorksheets();
                        unset($cell, $worksheet, $spreadsheet);
                        gc_collect_cycles();
                    }
                }

                return [
                    'sheet' => $sheetName,
                    'header_row' => $headerRow,
                    'headers' => array_values($columnMap),
                    'rows' => $rows,
                ];
            }
        } catch (PbbImportException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new PbbImportException('File Excel rusak, berpassword, atau format aslinya tidak sesuai ekstensi.', previous: $exception);
        }

        throw new PbbImportException('Header data tidak ditemukan. Minimal sertakan kolom nop, tax_year, dan nama_wp_sppt.');
    }

    /** @return array<string, string> Sheet title => internal XLSX path */
    private function xlsxWorksheetTargets(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new PbbImportException('Arsip Excel tidak dapat dibaca. Simpan ulang sebagai XLSX tanpa password.');
        }

        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if (! is_string($workbookXml) || ! is_string($relationshipsXml)) {
                throw new PbbImportException('Struktur XLSX tidak lengkap. Simpan ulang file melalui Excel atau LibreOffice.');
            }

            $workbook = @simplexml_load_string($workbookXml);
            $relationships = @simplexml_load_string($relationshipsXml);
            if ($workbook === false || $relationships === false) {
                throw new PbbImportException('Struktur XLSX tidak dapat dibaca. Simpan ulang file melalui Excel atau LibreOffice.');
            }

            $relationshipNamespace = 'http://schemas.openxmlformats.org/package/2006/relationships';
            $documentRelationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $targets = [];
            foreach ($relationships->children($relationshipNamespace)->Relationship as $relationship) {
                $attributes = $relationship->attributes();
                if ((string) ($attributes['TargetMode'] ?? '') !== 'External') {
                    $targets[(string) $attributes['Id']] = (string) $attributes['Target'];
                }
            }

            $sheets = [];
            foreach ($workbook->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->sheets->sheet as $sheet) {
                $attributes = $sheet->attributes();
                $relationshipId = (string) $sheet->attributes($documentRelationshipNamespace)['id'];
                $target = $targets[$relationshipId] ?? null;
                if ($target === null) {
                    continue;
                }
                $target = str_replace('\\', '/', $target);
                $target = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
                if (str_starts_with($target, 'xl/worksheets/')) {
                    $sheets[(string) $attributes['name']] = $target;
                }
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /**
     * Reads XLSX XML as a stream to find only row ranges with actual values/formulas.
     * It deliberately ignores blank cells which exist solely because of Excel formatting.
     *
     * @return array<int, int>
     */
    private function xlsxContentChunkStarts(string $path, string $sheetTarget, int $headerRow): array
    {
        $reader = new \XMLReader();
        $source = 'zip://'.str_replace('\\', '/', $path).'#'.$sheetTarget;
        if (! $reader->open($source, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new PbbImportException('Sheet Data tidak dapat dibaca. Simpan ulang file Excel lalu coba kembali.');
        }

        $starts = [];
        $dataRows = 0;
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'c') {
                    continue;
                }

                $coordinate = $reader->getAttribute('r');
                if (! is_string($coordinate) || preg_match('/^[A-Z]+(\d+)$/i', $coordinate, $match) !== 1) {
                    continue;
                }
                $rowNumber = (int) $match[1];
                if ($rowNumber <= $headerRow) {
                    continue;
                }

                $cellXml = $reader->readOuterXML();
                if (! $this->xlsxCellHasValue($cellXml)) {
                    continue;
                }

                if (! isset($starts[$rowNumber])) {
                    $dataRows++;
                    if ($dataRows > self::MAX_ROWS) {
                        $formattedMax = number_format(self::MAX_ROWS, 0, ',', '.');
                        throw new PbbImportException("File melebihi batas {$formattedMax} baris data. Pecah file menjadi beberapa bagian.");
                    }
                    $starts[$rowNumber] = (intdiv($rowNumber - 1, self::CHUNK_ROWS) * self::CHUNK_ROWS) + 1;
                }
            }
        } finally {
            $reader->close();
        }

        return array_values(array_unique(array_values($starts)));
    }

    private function xlsxCellHasValue(string $cellXml): bool
    {
        return preg_match('/<(?:[A-Za-z_][\w.-]*:)?(?:v|f|is)\b/i', $cellXml) === 1;
    }

    /** @return array<int, int> */
    private function legacyChunkStarts(int $highestRow, int $headerRow): array
    {
        if ($highestRow - $headerRow > 50_000) {
            throw new PbbImportException('Area file XLS terlalu besar untuk diperiksa dengan aman. Simpan sebagai XLSX atau CSV lalu coba kembali.');
        }

        return range($headerRow + 1, $highestRow, self::CHUNK_ROWS);
    }

    private function readFilter(int $start, int $end, ?array $columns = null): IReadFilter
    {
        $allowed = $columns === null ? range(1, self::MAX_COLUMNS) : $columns;

        return new class($start, $end, $allowed) implements IReadFilter {
            public function __construct(private int $start, private int $end, private array $columns) {}

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row >= $this->start && $row <= $this->end
                    && in_array(Coordinate::columnIndexFromString($columnAddress), $this->columns, true);
            }
        };
    }

    private function checkArchiveSize(string $path): void
    {
        if (file_get_contents($path, false, null, 0, 2) !== 'PK') {
            return;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new PbbImportException('Arsip Excel tidak dapat dibaca. Simpan ulang sebagai XLSX tanpa password.');
        }
        try {
            $total = 0;
            if ($zip->numFiles > 256) {
                throw new PbbImportException('Workbook terlalu kompleks. Salin hanya data pbb ke template baru.');
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $total += $entry['size'];
                if ($entry['size'] > 8 * 1024 * 1024 || $total > 24 * 1024 * 1024) {
                    throw new PbbImportException('Isi Excel setelah dibuka terlalu besar untuk diproses dengan aman. Hapus sheet/gambar/format yang tidak diperlukan, pecah file, atau gunakan CSV.');
                }
            }
        } finally {
            $zip->close();
        }
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

        $missing = PbbImportSchema::missingMinimumHeaders(array_values($bestMap));
        if ($bestRow === null || $missing !== []) {
            throw new PbbImportException('Header data tidak lengkap. Kolom wajib yang belum dikenali: '.implode(', ', $missing ?: PbbImportSchema::MINIMUM_HEADERS).'.');
        }
        $duplicates = $this->duplicateCanonicalHeaders($rows[$bestRow] ?? []);
        if ($duplicates !== []) {
            throw new PbbImportException('Header ganda terdeteksi untuk kolom: '.implode(', ', $duplicates).'. Hapus kolom duplikat lalu coba lagi.');
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
                $values[$column] = $worksheet->cellExists([$column, $row])
                    ? $worksheet->getCell([$column, $row])->getValue() : null;
            }
            $map = $this->mapHeaders($values);
            if (count($map) > count($bestMap)) {
                $bestRow = $row;
                $bestMap = $map;
            }
        }

        if ($bestRow === null || PbbImportSchema::missingMinimumHeaders(array_values($bestMap)) !== []) {
            return null;
        }

        $headerValues = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headerValues[] = $worksheet->cellExists([$column, $bestRow])
                ? $worksheet->getCell([$column, $bestRow])->getValue() : null;
        }
        $duplicates = $this->duplicateCanonicalHeaders($headerValues);
        if ($duplicates !== []) {
            throw new PbbImportException('Header ganda terdeteksi untuk kolom: '.implode(', ', $duplicates).'. Hapus kolom duplikat lalu coba lagi.');
        }

        return [$bestRow, $bestMap];
    }

    /** @return array<int, string> */
    private function mapHeaders(array $values): array
    {
        $map = [];
        $seen = [];
        foreach ($values as $index => $value) {
            $canonical = PbbImportSchema::canonicalHeader($value);
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
            $canonical = PbbImportSchema::canonicalHeader($value);
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
