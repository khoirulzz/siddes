<?php

namespace Tests\Unit;

use App\Services\PopulationImportParser;
use App\Exceptions\PopulationImportException;
use App\Support\PopulationImportSchema;
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

    public function test_sparse_formatted_template_does_not_materialize_blank_cells(): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Data');
        $sheet->fromArray(PopulationImportSchema::COLUMNS, null, 'A1');
        foreach (['A', 'D', 'E', 'F', 'N', 'X', 'Y'] as $column) {
            $sheet->getStyle("{$column}2:{$column}10001")->getNumberFormat()->setFormatCode('@');
        }
        $sheet->getStyle('R2:R10001')->getNumberFormat()->setFormatCode('dd-mm-yyyy');
        $sheet->setCellValueExplicit('A2', '0000000000000001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('N2', '0000000000000002', DataType::TYPE_STRING);
        $sheet->setCellValue('O2', 'Penduduk Uji');
        $path = tempnam(sys_get_temp_dir(), 'population-sparse-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        unset($sheet, $book);
        gc_collect_cycles();
        gc_mem_caches();
        memory_reset_peak_usage();
        $baseline = memory_get_usage(true);
        try {
            $parsed = (new PopulationImportParser())->parse(new UploadedFile($path, 'data.xlsx', null, null, true));
            $this->assertCount(1, $parsed['rows']);
            $this->assertSame('0000000000000002', $parsed['rows'][0]['_cells']['nik']['value']);
            $this->assertLessThan(64 * 1024 * 1024, memory_get_peak_usage(true) - $baseline);
        } finally {
            unlink($path);
        }
    }

    public function test_legacy_10000_row_formatting_with_only_filled_rows_is_accepted(): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Data');
        $sheet->fromArray(['no_kk', 'nik', 'nama_lengkap'], null, 'A1');
        $sheet->getStyle('A2:C10001')->getNumberFormat()->setFormatCode('@');
        $sheet->setCellValueExplicit('A2', '0000000000000001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', '0000000000000002', DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'Penduduk Uji');
        $path = tempnam(sys_get_temp_dir(), 'population-legacy-layout-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        try {
            $parsed = (new PopulationImportParser())->parse(new UploadedFile($path, 'data.xlsx', null, null, true));
            $this->assertCount(1, $parsed['rows']);
            $this->assertSame(2, $parsed['rows'][0]['_row']);
        } finally {
            unlink($path);
        }
    }

    public function test_chunk_boundaries_preserve_rows_formulas_and_excel_dates(): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Data');
        $sheet->fromArray(['no_kk', 'nik', 'nama_lengkap', 'tanggal_lahir'], null, 'A3');
        foreach ([4, 503, 504, 1003, 1004] as $row) {
            $sheet->setCellValueExplicit("A{$row}", '0000000000000001', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", str_pad((string) $row, 16, '0', STR_PAD_LEFT), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", 'Penduduk Uji');
            $sheet->setCellValue("D{$row}", ExcelDate::PHPToExcel(new \DateTimeImmutable('2000-01-01')));
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
        }
        $sheet->setCellValue('B504', '=1+1');
        $path = tempnam(sys_get_temp_dir(), 'population-chunks-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        try {
            $parsed = (new PopulationImportParser())->parse(new UploadedFile($path, 'data.xlsx', null, null, true));
            $this->assertSame(3, $parsed['header_row']);
            $this->assertSame([4, 503, 504, 1003, 1004], array_column($parsed['rows'], '_row'));
            foreach ($parsed['rows'] as $row) {
                $this->assertTrue($row['_cells']['tanggal_lahir']['is_date']);
            }
            $this->assertSame(DataType::TYPE_FORMULA, $parsed['rows'][2]['_cells']['nik']['type']);
        } finally {
            unlink($path);
        }
    }

    public function test_oversized_expanded_archive_is_rejected_before_workbook_loading(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'population-archive-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('xl/worksheets/sheet1.xml', str_repeat(' ', 8 * 1024 * 1024 + 1));
        $zip->close();
        try {
            $this->expectException(PopulationImportException::class);
            $this->expectExceptionMessage('setelah dibuka terlalu besar');
            (new PopulationImportParser())->parse(new UploadedFile($path, 'data.xlsx', null, null, true));
        } finally {
            unlink($path);
        }
    }

    public function test_sparse_layout_uses_filled_rows_instead_of_formatted_row_height_for_the_limit(): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(['no_kk', 'nik', 'nama_lengkap'], null, 'A1');
        $sheet->setCellValue('C6002', 'Penduduk Uji');
        $path = tempnam(sys_get_temp_dir(), 'population-limit-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        try {
            $parsed = (new PopulationImportParser())->parse(new UploadedFile($path, 'data.xlsx', null, null, true));
            $this->assertCount(1, $parsed['rows']);
            $this->assertSame(6002, $parsed['rows'][0]['_row']);
        } finally {
            unlink($path);
        }
    }

    public function test_more_than_6000_filled_rows_are_rejected(): void
    {
        $contents = "no_kk;nik;nama_lengkap\n".str_repeat("0000000000000001;0000000000000002;Penduduk Uji\n", PopulationImportParser::MAX_ROWS + 1);
        $file = $this->temporaryUpload($contents, 'melebihi-batas.csv', 'text/csv');

        $this->expectException(PopulationImportException::class);
        $this->expectExceptionMessage('melebihi batas 6.000 baris');
        (new PopulationImportParser())->parse($file);
    }
}
