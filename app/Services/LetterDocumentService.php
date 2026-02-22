<?php

namespace App\Services;

use App\Helpers\WordTemplateHelper;
use App\Models\LetterNumberCounter;
use App\Models\LetterServiceRequest;
use App\Models\PopulationRecord;
use App\Support\LetterSchema;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

class LetterDocumentService
{
    private const ROMAN_MONTHS = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    private const MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private const DAY_NAMES = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    public function generateTicketNumber(): string
    {
        do {
            $ticket = sprintf('SRT-%s-%s', now()->format('ymd'), Str::upper(Str::random(4)));
        } while (LetterServiceRequest::where('ticket_number', $ticket)->exists());

        return $ticket;
    }

    /**
     * @return array{code:string,sequence:int,sequence_padded:string,official_number:string}
     */
    public function issueLetterNumber(string $letterType, ?Carbon $issuedAt = null): array
    {
        $issuedAt ??= now();
        $year = (int) $issuedAt->format('Y');
        $month = (int) $issuedAt->format('n');
        $code = LetterSchema::codeForType($letterType);

        if (! Schema::hasTable('letter_number_counters')) {
            return $this->fallbackIssueLetterNumber($year, $month, $code);
        }

        $sequence = DB::transaction(function () use ($year, $code): int {
            $counter = LetterNumberCounter::query()
                ->where('year', $year)
                ->where('letter_code', $code)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = LetterNumberCounter::create([
                    'year' => $year,
                    'letter_code' => $code,
                    'last_number' => 0,
                ]);
            }

            $counter->last_number += 1;
            $counter->save();

            return (int) $counter->last_number;
        });

        $padded = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $romanMonth = self::ROMAN_MONTHS[$month] ?? '';
        $official = "{$padded}/{$code}/LMBG/{$romanMonth}/{$year}";

        return [
            'code' => $code,
            'sequence' => $sequence,
            'sequence_padded' => $padded,
            'official_number' => $official,
        ];
    }

    /**
     * @return array{code:string,sequence:int,sequence_padded:string,official_number:string}
     */
    private function fallbackIssueLetterNumber(int $year, int $month, string $code): array
    {
        $next = 1;

        if (
            Schema::hasTable('letter_service_requests')
            && Schema::hasColumn('letter_service_requests', 'letter_code')
            && Schema::hasColumn('letter_service_requests', 'letter_sequence')
        ) {
            $last = (int) LetterServiceRequest::query()
                ->where('letter_code', $code)
                ->max('letter_sequence');
            $next = $last + 1;
        }

        $padded = str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        $romanMonth = self::ROMAN_MONTHS[$month] ?? '';
        $official = "{$padded}/{$code}/LMBG/{$romanMonth}/{$year}";

        return [
            'code' => $code,
            'sequence' => $next,
            'sequence_padded' => $padded,
            'official_number' => $official,
        ];
    }

    /**
     * @return array{path:string,name:string,mime:string}
     */
    public function buildDownload(LetterServiceRequest $letter, string $format): array
    {
        $format = strtolower($format);
        if ($format === 'docx') {
            return $this->buildDocxDownload($letter);
        }

        if ($format !== 'pdf') {
            throw new RuntimeException('Format dokumen tidak didukung.');
        }

        return $this->buildPdfDownload($letter);
    }

    public function ensureTemplateExists(string $letterType): void
    {
        $templatePath = $this->templatePath($letterType);
        if (! $templatePath) {
            return;
        }

        if (! is_file($templatePath)) {
            throw new RuntimeException('Template surat tidak ditemukan. Silakan hubungi admin.');
        }
    }

    /**
     * @return array{path:string,name:string,mime:string}
     */
    private function buildDocxDownload(LetterServiceRequest $letter): array
    {
        $data = $this->buildDocumentData($letter);
        $templatePath = $this->templatePath($letter->letter_type);

        if ($templatePath && is_file($templatePath)) {
            $outputPath = WordTemplateHelper::fillTemplate($templatePath, $data);
        } else {
            $outputPath = $this->buildManualDocx($letter);
        }

        return [
            'path' => $outputPath,
            'name' => $this->downloadFileName($letter, 'docx'),
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    /**
     * @return array{path:string,name:string,mime:string}
     */
    private function buildPdfDownload(LetterServiceRequest $letter): array
    {
        $data = $this->buildDocumentData($letter);
        $outputPath = $this->tempFilePath('pdf');
        $viewName = $this->pdfViewForType($letter->letter_type);
        $letterNumber = $this->buildLetterNumber($letter, $data);
        $issuedAt = $letter->submitted_at ?? $letter->requested_at ?? now();

        $html = view($viewName, [
            'letter' => $letter,
            'data' => $data,
            'villageName' => config('village.name', PopulationRecord::DEFAULT_VILLAGE),
            'regencyName' => $data['kabupaten'] ?? PopulationRecord::DEFAULT_REGENCY,
            'districtName' => $data['kecamatan'] ?? PopulationRecord::DEFAULT_DISTRICT,
            'provinceName' => $data['provinsi'] ?? PopulationRecord::DEFAULT_PROVINCE,
            'postalCode' => $data['kode_pos'] ?? PopulationRecord::DEFAULT_POSTAL_CODE,
            'villageAddress' => config('village.address', ''),
            'logoUrl' => config('village.logo_url'),
            'letterNumber' => $letterNumber,
            'issuedAt' => $issuedAt,
        ])->render();

        try {
            if (class_exists(\Mpdf\Mpdf::class)) {
                $this->renderPdfWithMpdf($html, $outputPath);
            } else {
                $this->renderPdfWithDompdf($html, $outputPath);
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal menghasilkan PDF: ' . $e->getMessage(), 0, $e);
        }

        return [
            'path' => $outputPath,
            'name' => $this->downloadFileName($letter, 'pdf'),
            'mime' => 'application/pdf',
        ];
    }

    private function pdfViewForType(string $letterType): string
    {
        return match ($letterType) {
            LetterSchema::TYPE_SKTM => 'pdf.letters.sktm',
            LetterSchema::TYPE_SKD => 'pdf.letters.domisili',
            LetterSchema::TYPE_SKK => 'pdf.letters.skk',
            LetterSchema::TYPE_SPK => 'pdf.letters.spk',
            default => 'pdf.letters.sku',
        };
    }

    /**
     * @param array<string, string> $data
     */
    private function buildLetterNumber(LetterServiceRequest $letter, array $data): string
    {
        if (! empty($letter->official_number)) {
            return (string) $letter->official_number;
        }

        $code = $letter->letter_code ?: LetterSchema::codeForType($letter->letter_type);
        $year = $data['tahun'] ?? now()->format('Y');
        $romanMonth = $data['bulan_romawi'] ?? self::ROMAN_MONTHS[(int) now()->format('n')];
        $sequence = $letter->letter_sequence
            ? str_pad((string) $letter->letter_sequence, 3, '0', STR_PAD_LEFT)
            : '000';

        return "{$sequence}/{$code}/LMBG/{$romanMonth}/{$year}";
    }

    private function buildManualDocx(LetterServiceRequest $letter): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 1200,
            'marginRight' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1200,
        ]);

        $lines = $this->buildPdfLines($letter);
        $section->addText(strtoupper((string) $letter->letter_type), ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(1);

        foreach ($lines as $line) {
            $section->addText($line, ['size' => 11]);
        }

        $outputPath = $this->tempFilePath('docx');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * @return array<string, string>
     */
    private function buildDocumentData(LetterServiceRequest $letter): array
    {
        $citizen = PopulationRecord::query()->where('nik', $letter->nik)->first();
        $dynamic = is_array($letter->dynamic_data) ? $letter->dynamic_data : (json_decode((string) $letter->dynamic_data, true) ?: []);
        $dynamic = $this->sanitizeDynamic($dynamic);

        $issuedAt = $letter->submitted_at ?? $letter->requested_at ?? now();
        $monthNumber = (int) $issuedAt->format('n');
        $year = $issuedAt->format('Y');
        $officialNumber = $this->buildLetterNumber($letter, [
            'tahun' => $year,
            'bulan_romawi' => self::ROMAN_MONTHS[$monthNumber] ?? '',
        ]);
        $sequencePadded = $letter->letter_sequence
            ? str_pad((string) $letter->letter_sequence, 3, '0', STR_PAD_LEFT)
            : $this->extractSequence($officialNumber);

        $namaPemohon = $this->cleanText($letter->applicant_name ?: ($citizen?->resolvedName() ?: '-'));
        $tempatLahir = $this->cleanText($citizen?->resolvedBirthPlace() ?: ((string) ($dynamic['tempat_lahir'] ?? '-')));
        $tanggalLahir = $this->formatDate($citizen?->resolvedBirthDate(), '-');
        if ($tanggalLahir === '-') {
            $tanggalLahir = $this->normalizeDateString((string) ($dynamic['tanggal_lahir'] ?? ''), '-');
        }

        $jenisKelamin = $this->cleanText($citizen?->resolvedGender() ?: ((string) ($dynamic['jenis_kelamin'] ?? '-')));
        $agama = $this->cleanText($citizen?->resolvedReligion() ?: ((string) ($dynamic['agama'] ?? '-')));
        $pekerjaan = $this->cleanText($citizen?->resolvedOccupation() ?: ((string) ($dynamic['pekerjaan'] ?? '-')));
        $dusun = $this->cleanText($citizen?->resolvedHamlet() ?: ((string) ($dynamic['dusun'] ?? '-')));
        $rt = $this->cleanText($citizen?->resolvedRt() ?: ((string) ($dynamic['rt'] ?? '-')));
        $rw = $this->cleanText($citizen?->resolvedRw() ?: ((string) ($dynamic['rw'] ?? '-')));
        $desa = $this->cleanText($citizen?->resolvedVillage() ?: PopulationRecord::DEFAULT_VILLAGE);
        $kecamatan = $this->cleanText($citizen?->resolvedDistrict() ?: PopulationRecord::DEFAULT_DISTRICT);
        $kabupaten = $this->cleanText($citizen?->resolvedRegency() ?: PopulationRecord::DEFAULT_REGENCY);
        $provinsi = $this->cleanText($citizen?->resolvedProvince() ?: PopulationRecord::DEFAULT_PROVINCE);
        $kodePos = $this->cleanText($citizen?->resolvedPostalCode() ?: PopulationRecord::DEFAULT_POSTAL_CODE);

        $pemohonBirth = $citizen?->resolvedBirthDate();
        $usiaPemohon = $pemohonBirth?->age;
        if ($usiaPemohon === null && ! empty($dynamic['usia_pemohon'])) {
            $usiaPemohon = (int) $dynamic['usia_pemohon'];
        }

        $tglLahirAlmarhum = $this->parseDate((string) ($dynamic['tgl_lahir_almarhum'] ?? ''));
        $tglMeninggal = $this->parseDate((string) ($dynamic['tgl_meninggal'] ?? ''));
        $usiaAlmarhum = ! empty($dynamic['usia_almarhum'])
            ? (int) $dynamic['usia_almarhum']
            : $this->ageByRange($tglLahirAlmarhum, $tglMeninggal);
        $hariMeninggal = $this->cleanText((string) ($dynamic['hari_meninggal'] ?? ($tglMeninggal ? $this->dayName($tglMeninggal) : '-')));

        $data = [
            'nomor' => $letter->ticket_number ?: '-',
            'nomor_surat' => $officialNumber,
            'bulan_romawi' => self::ROMAN_MONTHS[$monthNumber] ?? '',
            'tanggal' => $issuedAt->format('d') . ' ' . (self::MONTH_NAMES[$monthNumber] ?? '') . ' ' . $year,
            'tanggal_angka' => $issuedAt->format('d'),
            'bulan' => self::MONTH_NAMES[$monthNumber] ?? '',
            'tahun' => $year,
            'nama' => $namaPemohon,
            'nama_pemohon' => $namaPemohon,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'ttl' => $this->cleanText(trim($tempatLahir . ', ' . $tanggalLahir, ' ,')),
            'nik' => $this->cleanText($letter->nik ?: '-'),
            'nkk' => $this->cleanText($letter->kk_number ?: ($citizen?->resolvedKkNumber() ?: '-')),
            'jenis_kelamin' => $jenisKelamin,
            'agama' => $agama,
            'pekerjaan' => $pekerjaan,
            'dusun' => $dusun,
            'rt' => $rt,
            'rw' => $rw,
            'desa' => $desa,
            'kecamatan' => $kecamatan,
            'kabupaten' => $kabupaten,
            'provinsi' => $provinsi,
            'kode_pos' => $kodePos,
            'alamat' => $this->cleanText($this->buildAddressText($letter, $citizen)),
            'keperluan' => $this->cleanText((string) ($dynamic['keperluan'] ?? 'Administrasi surat')),
            'nama_usaha' => $this->cleanText((string) ($dynamic['nama_usaha'] ?? '-')),
            'jenis_usaha' => $this->cleanText((string) ($dynamic['jenis_usaha'] ?? '-')),
            'alamat_usaha' => $this->cleanText((string) ($dynamic['alamat_usaha'] ?? '-')),
            'kehilangan_benda' => $this->cleanText((string) ($dynamic['kehilangan_benda'] ?? '-')),
            'lokasi_kehilangan' => $this->cleanText((string) ($dynamic['lokasi_kehilangan'] ?? '-')),
            'lama_hilang' => $this->cleanText((string) ($dynamic['lama_hilang'] ?? '-')),
            'dusun_asal' => $this->cleanText((string) ($dynamic['dusun_asal'] ?? $dusun)),
            'rt_asal' => $this->cleanText((string) ($dynamic['rt_asal'] ?? $rt)),
            'rw_asal' => $this->cleanText((string) ($dynamic['rw_asal'] ?? $rw)),
            'desa_asal' => $this->cleanText((string) ($dynamic['desa_asal'] ?? $desa)),
            'kecamatan_asal' => $this->cleanText((string) ($dynamic['kecamatan_asal'] ?? $kecamatan)),
            'kabupaten_asal' => $this->cleanText((string) ($dynamic['kabupaten_asal'] ?? $kabupaten)),
            'nama_almarhum' => $this->cleanText((string) ($dynamic['nama_almarhum'] ?? '-')),
            'jenis_kelamin_almarhum' => $this->cleanText((string) ($dynamic['jenis_kelamin_almarhum'] ?? '-')),
            'tgl_lahir_almarhum' => $this->formatDate($tglLahirAlmarhum, '-'),
            'usia_almarhum' => $usiaAlmarhum !== null ? (string) $usiaAlmarhum : '-',
            'alamat_almarhum' => $this->cleanText((string) ($dynamic['alamat_almarhum'] ?? '-')),
            'hari_meninggal' => $hariMeninggal,
            'tgl_meninggal' => $this->formatDate($tglMeninggal, '-'),
            'waktu_meninggal' => $this->cleanText((string) ($dynamic['waktu_meninggal'] ?? '-')),
            'tempat_meninggal' => $this->cleanText((string) ($dynamic['tempat_meninggal'] ?? '-')),
            'penyebab_meninggal' => $this->cleanText((string) ($dynamic['penyebab_meninggal'] ?? '-')),
            'hubungan_pemohon' => $this->cleanText((string) ($dynamic['hubungan_pemohon'] ?? '-')),
            'usia_pemohon' => $usiaPemohon !== null ? (string) $usiaPemohon : $this->cleanText((string) ($dynamic['usia_pemohon'] ?? '-')),
            'bojongireng' => $dusun,
        ];

        foreach (['sku_nomor', 'skd_nomor', 'skk_nomor', 'spk_nomor', 'sktm_nomor'] as $numberKey) {
            $data[$numberKey] = $sequencePadded;
        }

        if ($typeNumberKey = LetterSchema::numberPlaceholderForType($letter->letter_type)) {
            $data[$typeNumberKey] = $sequencePadded;
        }

        foreach ($dynamic as $key => $value) {
            if (! isset($data[$key])) {
                $data[$key] = $this->cleanText((string) $value);
            }
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function buildPdfLines(LetterServiceRequest $letter): array
    {
        $data = $this->buildDocumentData($letter);

        $lines = [
            "Nomor Surat: {$data['nomor_surat']}",
            "Jenis Surat: {$letter->letter_type}",
            '',
            "Nama Pemohon: {$data['nama_pemohon']}",
            "NIK: {$data['nik']}",
            "Alamat: {$data['alamat']}",
            '',
            "Tanggal Terbit: {$data['tanggal']}",
        ];

        if (! empty($data['keperluan']) && $data['keperluan'] !== '-') {
            $lines[] = "Keperluan: {$data['keperluan']}";
        }

        return $lines;
    }

    private function templatePath(string $letterType): ?string
    {
        $template = LetterSchema::templateForType($letterType);
        if (! $template) {
            return null;
        }

        return base_path('docs/' . $template);
    }

    private function buildAddressText(LetterServiceRequest $letter, ?PopulationRecord $citizen): string
    {
        if (! empty($letter->address)) {
            return (string) $letter->address;
        }

        if (! $citizen) {
            return '-';
        }

        $parts = [];
        if ($citizen->address_detail) {
            $parts[] = $citizen->address_detail;
        }
        if ($citizen->resolvedRt() !== '-') {
            $parts[] = 'RT ' . $citizen->resolvedRt();
        }
        if ($citizen->resolvedRw() !== '-') {
            $parts[] = 'RW ' . $citizen->resolvedRw();
        }
        if ($citizen->resolvedHamlet() !== '-') {
            $parts[] = 'Dusun ' . $citizen->resolvedHamlet();
        }

        $parts[] = $citizen->resolvedVillage();
        $parts[] = $citizen->resolvedDistrict();
        $parts[] = $citizen->resolvedRegency();
        $parts[] = $citizen->resolvedProvince();
        $parts[] = $citizen->resolvedPostalCode();

        return implode(', ', array_filter($parts));
    }

    private function cleanText(string $value): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n", "\t"], ' ', $value)) ?? '');

        return $clean !== '' ? $clean : '-';
    }

    private function downloadFileName(LetterServiceRequest $letter, string $extension): string
    {
        $number = Str::slug((string) ($letter->official_number ?: $letter->ticket_number));
        $slugType = Str::slug($letter->letter_type ?: 'surat');

        if ($number === '') {
            $number = 'surat-' . ($letter->id ?: Str::lower((string) Str::ulid()));
        }
        if ($slugType === '') {
            $slugType = 'surat';
        }

        return "{$number}-{$slugType}.{$extension}";
    }

    private function tempFilePath(string $extension): string
    {
        $dir = storage_path('app/tmp-letters');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/surat_' . Str::ulid() . '.' . $extension;
    }

    /**
     * @param array<string, mixed> $dynamic
     * @return array<string, string>
     */
    private function sanitizeDynamic(array $dynamic): array
    {
        $result = [];
        foreach ($dynamic as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }

            $result[(string) $key] = $text;
        }

        return $result;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(?Carbon $date, string $fallback = '-'): string
    {
        return $date ? $date->format('d-m-Y') : $fallback;
    }

    private function normalizeDateString(string $value, string $fallback = '-'): string
    {
        $date = $this->parseDate($value);

        return $date ? $date->format('d-m-Y') : $fallback;
    }

    private function dayName(Carbon $date): string
    {
        return self::DAY_NAMES[$date->format('l')] ?? $date->format('l');
    }

    private function ageByRange(?Carbon $birthDate, ?Carbon $endDate): ?int
    {
        if (! $birthDate || ! $endDate) {
            return null;
        }

        return $birthDate->diffInYears($endDate);
    }

    private function extractSequence(string $officialNumber): string
    {
        if (preg_match('/^(\d{1,3})\//', $officialNumber, $matches) === 1) {
            return str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        return '000';
    }

    private function renderPdfWithDompdf(string $html, string $outputPath): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times New Roman');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outputPath, $dompdf->output());
    }

    private function renderPdfWithMpdf(string $html, string $outputPath): void
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 22,
            'margin_right' => 22,
            'margin_top' => 22,
            'margin_bottom' => 22,
            'margin_header' => 8,
            'margin_footer' => 8,
            'default_font' => 'timesnewroman',
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
    }
}
