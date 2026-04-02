<?php

namespace Tests\Unit;

use App\Support\SpreadsheetImportHelper;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelReaderType;
use PHPUnit\Framework\TestCase;

class SpreadsheetImportHelperTest extends TestCase
{
    public function test_it_detects_semicolon_delimiter_from_csv_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'csv-semi-');
        file_put_contents($path, "nik;nama_lengkap;no_kk\n3326010201010001;Uji Coba;3326010101010001\n");

        $file = new UploadedFile($path, 'penduduk.csv', null, null, true);
        $delimiter = SpreadsheetImportHelper::detectCsvDelimiter($file);

        @unlink($path);

        $this->assertSame(';', $delimiter);
    }

    public function test_it_resolves_reader_type_from_file_extension(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-type-');
        file_put_contents($path, 'dummy');

        $csvFile = new UploadedFile($path, 'data.csv', null, null, true);
        $xlsxFile = new UploadedFile($path, 'data.xlsx', null, null, true);
        $xlsFile = new UploadedFile($path, 'data.xls', null, null, true);
        $unknownFile = new UploadedFile($path, 'data.unknown', null, null, true);

        $this->assertSame(ExcelReaderType::CSV, SpreadsheetImportHelper::resolveReaderType($csvFile));
        $this->assertSame(ExcelReaderType::XLSX, SpreadsheetImportHelper::resolveReaderType($xlsxFile));
        $this->assertSame(ExcelReaderType::XLS, SpreadsheetImportHelper::resolveReaderType($xlsFile));
        $this->assertNull(SpreadsheetImportHelper::resolveReaderType($unknownFile));

        @unlink($path);
    }
}

