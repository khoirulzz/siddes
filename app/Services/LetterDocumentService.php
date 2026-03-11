<?php

namespace App\Services;

use App\Helpers\WordTemplateHelper;
use App\Models\LetterNumberCounter;
use App\Models\LetterServiceRequest;
use App\Models\PopulationRecord;
use App\Support\LetterSchema;
use App\Support\PublicMedia;
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
            $outputPath = WordTemplateHelper::fillTemplate(
                $templatePath,
                $data,
                $this->buildLiteralReplacements($data)
            );
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
        $pdfLetterNumber = $this->buildTemplateLetterNumberForPdf($letter, $data, $letterNumber);
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
            'logoUrl' => $this->resolvePdfLogoUrl(),
            'letterNumber' => $letterNumber,
            'pdfLetterNumber' => $pdfLetterNumber,
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
            LetterSchema::TYPE_SKB => 'pdf.letters.skb',
            LetterSchema::TYPE_SKM => 'pdf.letters.skm',
            LetterSchema::TYPE_SPPK => 'pdf.letters.skck',
            LetterSchema::TYPE_SPP => 'pdf.letters.spp',
            LetterSchema::TYPE_SKKER => 'pdf.letters.skker',
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
        $headName = $this->cleanText((string) config('village.head_name', 'ABDUL HADI'));
        $headPosition = $this->cleanText((string) config(
            'village.head_position',
            'Kepala Desa ' . config('village.name', PopulationRecord::DEFAULT_VILLAGE)
        ));

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

        $namaPemohon = $this->firstMeaningful([
            $letter->applicant_name,
            $citizen?->resolvedName(),
            (string) ($dynamic['nama_pemohon'] ?? ''),
        ]);
        $tempatLahir = $this->firstMeaningful([
            $citizen?->resolvedBirthPlace(),
            (string) ($dynamic['tempat_lahir'] ?? ''),
        ]);
        $tanggalLahir = $this->formatDate($citizen?->resolvedBirthDate(), '-');
        if ($tanggalLahir === '-') {
            $tanggalLahir = $this->normalizeDateString((string) ($dynamic['tanggal_lahir'] ?? ''), '-');
        }

        $jenisKelamin = $this->firstMeaningful([
            $citizen?->resolvedGender(),
            (string) ($dynamic['jenis_kelamin'] ?? ''),
        ]);
        $agama = $this->firstMeaningful([
            $citizen?->resolvedReligion(),
            (string) ($dynamic['agama'] ?? ''),
        ]);
        $pekerjaan = $this->firstMeaningful([
            $citizen?->resolvedOccupation(),
            (string) ($dynamic['pekerjaan'] ?? ''),
        ]);
        $dusun = $this->firstMeaningful([
            $citizen?->resolvedHamlet(),
            (string) ($dynamic['dusun'] ?? ''),
        ]);
        $rt = $this->firstMeaningful([
            $citizen?->resolvedRt(),
            (string) ($dynamic['rt'] ?? ''),
        ]);
        $rw = $this->firstMeaningful([
            $citizen?->resolvedRw(),
            (string) ($dynamic['rw'] ?? ''),
        ]);
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
        $tanggalNikah = $this->parseDate((string) ($dynamic['tanggal_nikah'] ?? ''));
        $statusKawin = $this->firstMeaningful([
            (string) ($dynamic['status_kawin'] ?? ''),
            (string) ($citizen?->status_perkawinan ?? ''),
        ]);
        $noHp = $this->cleanText((string) ($dynamic['no_hp'] ?? ($letter->phone ?: '-')));
        $emailPemohon = $this->cleanText((string) ($dynamic['email'] ?? ($letter->email ?: '-')));
        $tempatLahirSuami = $this->cleanText((string) ($dynamic['tempat_lahir_suami'] ?? $tempatLahir));
        $tanggalLahirSuami = $this->normalizeDateString((string) ($dynamic['tanggal_lahir_suami'] ?? ''), $tanggalLahir);
        $tempatLahirIstri = $this->cleanText((string) ($dynamic['tempat_lahir_istri'] ?? $tempatLahir));
        $tanggalLahirIstri = $this->normalizeDateString((string) ($dynamic['tanggal_lahir_istri'] ?? ''), $tanggalLahir);

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
            'desa_tujuan' => $this->cleanText((string) ($dynamic['desa_tujuan'] ?? '-')),
            'kec_tujuan' => $this->cleanText((string) ($dynamic['kec_tujuan'] ?? '-')),
            'kota_tujuan' => $this->cleanText((string) ($dynamic['kota_tujuan'] ?? '-')),
            'prov_tujuan' => $this->cleanText((string) ($dynamic['prov_tujuan'] ?? '-')),
            'dusun_asal' => $this->cleanText((string) ($dynamic['dusun_asal'] ?? $dusun)),
            'rt_asal' => $this->cleanText((string) ($dynamic['rt_asal'] ?? $rt)),
            'rw_asal' => $this->cleanText((string) ($dynamic['rw_asal'] ?? $rw)),
            'desa_asal' => $this->cleanText((string) ($dynamic['desa_asal'] ?? $desa)),
            'kecamatan_asal' => $this->cleanText((string) ($dynamic['kecamatan_asal'] ?? $kecamatan)),
            'kabupaten_asal' => $this->cleanText((string) ($dynamic['kabupaten_asal'] ?? $kabupaten)),
            'nama_suami' => $this->cleanText((string) ($dynamic['nama_suami'] ?? '-')),
            'ayah_suami' => $this->cleanText((string) ($dynamic['ayah_suami'] ?? '-')),
            'tempat_lahir_suami' => $tempatLahirSuami,
            'tanggal_lahir_suami' => $tanggalLahirSuami,
            'nik_suami' => $this->cleanText((string) ($dynamic['nik_suami'] ?? '-')),
            'pekerjaan_suami' => $this->cleanText((string) ($dynamic['pekerjaan_suami'] ?? '-')),
            'alamat_suami' => $this->cleanText((string) ($dynamic['alamat_suami'] ?? '-')),
            'Alamat_suami' => $this->cleanText((string) ($dynamic['Alamat_suami'] ?? ($dynamic['alamat_suami'] ?? '-'))),
            'nama_istri' => $this->cleanText((string) ($dynamic['nama_istri'] ?? '-')),
            'ayah_istri' => $this->cleanText((string) ($dynamic['ayah_istri'] ?? '-')),
            'tempat_lahir_istri' => $tempatLahirIstri,
            'tanggal_lahir_istri' => $tanggalLahirIstri,
            'nik_istri' => $this->cleanText((string) ($dynamic['nik_istri'] ?? '-')),
            'pekerjaan_istri' => $this->cleanText((string) ($dynamic['pekerjaan_istri'] ?? '-')),
            'alamat_istri' => $this->cleanText((string) ($dynamic['alamat_istri'] ?? '-')),
            'Alamat_istri' => $this->cleanText((string) ($dynamic['Alamat_istri'] ?? ($dynamic['alamat_istri'] ?? '-'))),
            'tanggal_nikah' => $this->formatDate($tanggalNikah, '-'),
            'mas_kawin' => $this->cleanText((string) ($dynamic['mas_kawin'] ?? '-')),
            'saksi_suami' => $this->cleanText((string) ($dynamic['saksi_suami'] ?? '-')),
            'hub_dg_suami' => $this->cleanText((string) ($dynamic['hub_dg_suami'] ?? '-')),
            'saksi_istri' => $this->cleanText((string) ($dynamic['saksi_istri'] ?? '-')),
            'hub_dg_istri' => $this->cleanText((string) ($dynamic['hub_dg_istri'] ?? '-')),
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
            'status_kawin' => $statusKawin,
            'no_hp' => $noHp,
            'email' => $emailPemohon,
            'jumlah_penghasilan' => $this->cleanText((string) ($dynamic['jumlah_penghasilan'] ?? '-')),
            'terbilang_penghasilan' => $this->cleanText((string) ($dynamic['terbilang_penghasilan'] ?? '-')),
            'tujuan' => $this->cleanText((string) ($dynamic['tujuan'] ?? '-')),
            'nama_rt' => $this->cleanText((string) ($dynamic['nama_rt'] ?? '-')),
            'nama_rw' => $this->cleanText((string) ($dynamic['nama_rw'] ?? '-')),
            'usia_pemohon' => $usiaPemohon !== null ? (string) $usiaPemohon : $this->cleanText((string) ($dynamic['usia_pemohon'] ?? '-')),
            'bojongireng' => $dusun,
            'nama_kepala_desa' => $headName,
            'kepala_desa' => $headName,
            'kepala_desa_nama' => $headName,
            'nama_kades' => $headName,
            'jabatan_kepala_desa' => $headPosition,
            'kepala_desa_jabatan' => $headPosition,
            'jabatan_penandatangan' => $headPosition,
            'penandatangan' => $headName,
        ];

        foreach ([
            'sku_nomor',
            'skd_nomor',
            'skk_nomor',
            'spk_nomor',
            'sktm_nomor',
            'skb_nomor',
            'skm_nomor',
            'sppk_nomor',
            'spp_nomor',
            'skker_nomor',
            'skkerja_nomor',
        ] as $numberKey) {
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
     * @param array<string, string> $data
     * @return array<string, string>
     */
    private function buildLiteralReplacements(array $data): array
    {
        $headName = trim((string) ($data['nama_kepala_desa'] ?? ''));
        $headPosition = trim((string) ($data['jabatan_kepala_desa'] ?? ''));
        $issuedDate = trim((string) ($data['tanggal'] ?? ''));
        $ayahSuami = trim((string) ($data['ayah_suami'] ?? ''));
        $tanggalNikah = trim((string) ($data['tanggal_nikah'] ?? ''));

        $replacements = [];
        if ($headName !== '' && $headName !== '-') {
            $replacements['ABDUL HADI'] = Str::upper($headName);
            $replacements['Abdul Hadi'] = $headName;
        }

        if ($headPosition !== '' && $headPosition !== '-') {
            $replacements['KEPALA DESA LAMBANGGELUN'] = Str::upper($headPosition);
            $replacements['Kepala Desa Lambanggelun'] = $headPosition;
        }

        if ($issuedDate !== '' && $issuedDate !== '-') {
            $replacements['{tanggal)'] = $issuedDate;
        }

        if ($ayahSuami !== '' && $ayahSuami !== '-') {
            $replacements['{ayah_suami)'] = $ayahSuami;
        }

        if ($tanggalNikah !== '' && $tanggalNikah !== '-') {
            $replacements['{tanggal_nikah)'] = $tanggalNikah;
        }

        return $replacements;
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

    /**
     * @param array<string, string> $data
     */
    private function buildTemplateLetterNumberForPdf(
        LetterServiceRequest $letter,
        array $data,
        string $defaultNumber
    ): string {
        $numberKey = LetterSchema::numberPlaceholderForType($letter->letter_type);
        $sequence = $numberKey ? trim((string) ($data[$numberKey] ?? '')) : '';

        if ($sequence === '' || $sequence === '-') {
            $sequence = $letter->letter_sequence
                ? str_pad((string) $letter->letter_sequence, 3, '0', STR_PAD_LEFT)
                : '000';
        }

        $romanMonth = trim((string) ($data['bulan_romawi'] ?? ''));
        if ($romanMonth === '') {
            $romanMonth = self::ROMAN_MONTHS[(int) now()->format('n')] ?? '';
        }

        $year = trim((string) ($data['tahun'] ?? ''));
        if ($year === '') {
            $year = now()->format('Y');
        }

        return match ($letter->letter_type) {
            LetterSchema::TYPE_SKB => "470/{$sequence}/DS-LBG/{$romanMonth}/{$year}",
            LetterSchema::TYPE_SKM => "472.2/{$sequence}/DS-LBG/{$romanMonth}/{$year}",
            LetterSchema::TYPE_SPPK => "470/{$sequence}/DS-13/{$romanMonth}/{$year}",
            LetterSchema::TYPE_SKKER => "470/{$sequence}/LBG/{$romanMonth}/{$year}",
            default => $defaultNumber,
        };
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

    /**
     * @param array<int, mixed> $candidates
     */
    private function firstMeaningful(array $candidates, string $fallback = '-'): string
    {
        foreach ($candidates as $candidate) {
            $value = $this->normalizeMeaningful($candidate);
            if ($value !== null) {
                return $this->cleanText($value);
            }
        }

        return $fallback;
    }

    private function normalizeMeaningful(mixed $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $value = trim((string) $candidate);
        if ($value === '' || $value === '-') {
            return null;
        }

        return $value;
    }

    private function resolvePdfLogoUrl(): string
    {
        $configuredLogo = trim((string) config('village.logo_url', ''));

        if (str_starts_with($configuredLogo, 'data:')) {
            return $configuredLogo;
        }

        $localFromConfig = $this->localLogoPathFromValue($configuredLogo);
        if ($localFromConfig) {
            $localSource = $this->imageSourceFromPath($localFromConfig);
            if ($localSource !== null) {
                return $localSource;
            }
        }

        if ($configuredLogo !== '' && preg_match('/^https?:\/\//i', $configuredLogo) === 1) {
            $remotePath = (string) parse_url($configuredLogo, PHP_URL_PATH);
            if ($this->imageMimeFromPath($remotePath) === 'image/svg+xml') {
                $svgFallbackPath = public_path('assets/images/logo_pekalongan.svg');
                if (is_file($svgFallbackPath)) {
                    return $svgFallbackPath;
                }
            } else {
                $remoteDataUri = $this->remoteLogoToDataUri($configuredLogo);
                if ($remoteDataUri !== null) {
                    return $remoteDataUri;
                }
            }
        }

        $fallbackPath = public_path('assets/images/logo_pekalongan.svg');
        $fallbackSource = $this->imageSourceFromPath($fallbackPath);
        if ($fallbackSource !== null) {
            return $fallbackSource;
        }

        return PublicMedia::toUrl($configuredLogo) ?? $configuredLogo;
    }

    private function localLogoPathFromValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value) === 1) {
            $host = (string) parse_url($value, PHP_URL_HOST);
            $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host === '' || $appHost === '' || strcasecmp($host, $appHost) !== 0) {
                return null;
            }

            $value = (string) parse_url($value, PHP_URL_PATH);
        }

        $normalized = PublicMedia::normalizePath($value);
        if (! $normalized) {
            return null;
        }

        $candidates = [
            storage_path('app/public/' . $normalized),
            public_path('storage/' . $normalized),
            public_path($normalized),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function remoteLogoToDataUri(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $mime = $this->imageMimeFromPath($path);
        if ($mime === 'image/svg+xml') {
            return null;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 2, 'ignore_errors' => true],
            'https' => ['timeout' => 2, 'ignore_errors' => true],
        ]);

        $content = @file_get_contents($url, false, $context);
        if (! is_string($content) || $content === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function imagePathToDataUri(string $path): ?string
    {
        $content = @file_get_contents($path);
        if (! is_string($content) || $content === '') {
            return null;
        }

        $mime = $this->imageMimeFromPath($path);
        if ($mime === 'image/svg+xml') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function imageSourceFromPath(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        if ($this->imageMimeFromPath($path) === 'image/svg+xml') {
            return $path;
        }

        return $this->imagePathToDataUri($path);
    }

    private function imageMimeFromPath(string $path): string
    {
        return match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
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
