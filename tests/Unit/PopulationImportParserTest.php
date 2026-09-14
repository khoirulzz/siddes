<?php

namespace Tests\Unit;

use App\Services\PopulationImportParser;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class PopulationImportParserTest extends TestCase
{
    public function test_it_finds_an_aliased_header_after_a_preamble_in_semicolon_csv(): void
    {
        $file = $this->temporaryUpload(
            "Laporan Kependudukan\nNomor KK;Nomor Induk Kependudukan;Nama Anggota Keluarga\n'3326010101010001;'3326010101800001;Budi Santoso\n",
            'penduduk.csv',
            'text/csv',
        );

        $parsed = (new PopulationImportParser())->parse($file);

        $this->assertSame(2, $parsed['header_row']);
        $this->assertSame('3326010101010001', ltrim($parsed['rows'][0]['_cells']['no_kk']['value'], "'"));
        $this->assertSame('Budi Santoso', $parsed['rows'][0]['_cells']['nama_lengkap']['value']);
    }

    public function test_it_reads_utf16_excel_text_with_tab_delimiter(): void
    {
        $utf8 = "Nomor KK\tNIK\tNama Lengkap\r\n3326010101010001\t3326010101800001\tBudi Santoso\r\n";
        $contents = "\xFF\xFE".mb_convert_encoding($utf8, 'UTF-16LE', 'UTF-8');
        $file = $this->temporaryUpload($contents, 'penduduk.txt', 'text/plain');

        $parsed = (new PopulationImportParser())->parse($file);

        $this->assertSame('3326010101800001', $parsed['rows'][0]['_cells']['nik']['value']);
        $this->assertSame('Budi Santoso', $parsed['rows'][0]['_cells']['nama_lengkap']['value']);
    }

    public function test_it_prefers_data_sheet_and_preserves_excel_cell_types(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Petunjuk')->setCellValue('A1', 'Petunjuk penggunaan');
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data');
        $sheet->fromArray(['no_kk', 'nik', 'nama_lengkap'], null, 'A1');
        $sheet->setCellValueExplicit('A2', '3326010101010001', DataType::TYPE_STRING);
        $sheet->setCellValue('B2', 3326010101800001);
        $sheet->setCellValue('C2', 'Budi Santoso');

        $path = tempnam(sys_get_temp_dir(), 'population-xlsx-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile($path, 'penduduk.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $parsed = (new PopulationImportParser())->parse($file);

        $this->assertSame('Data', $parsed['sheet']);
        $this->assertSame(DataType::TYPE_STRING, $parsed['rows'][0]['_cells']['no_kk']['type']);
        $this->assertSame(DataType::TYPE_NUMERIC, $parsed['rows'][0]['_cells']['nik']['type']);

        @unlink($path);
    }

    public function test_it_reads_legacy_xls_and_marks_real_excel_dates(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['no_kk', 'nik', 'nama_lengkap', 'tanggal_lahir'], null, 'A1');
        $sheet->setCellValueExplicit('A2', '3326010101010001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', '3326010101800001', DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'Budi Santoso');
        $sheet->setCellValue('D2', ExcelDate::PHPToExcel(new \DateTimeImmutable('1980-01-01')));
        $sheet->getStyle('D2')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $path = tempnam(sys_get_temp_dir(), 'population-xls-').'.xls';
        (new Xls($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile($path, 'penduduk.xls', 'application/vnd.ms-excel', null, true);
        $parsed = (new PopulationImportParser())->parse($file);

        $this->assertTrue($parsed['rows'][0]['_cells']['tanggal_lahir']['is_date']);
        $this->assertSame(DataType::TYPE_STRING, $parsed['rows'][0]['_cells']['nik']['type']);

        @unlink($path);
    }

    private function temporaryUpload(string $contents, string $name, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'population-import-');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $mime, null, true);
    }
}
