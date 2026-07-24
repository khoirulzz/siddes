<?php

namespace App\Support;

use Illuminate\Support\Str;

class PopulationStatHelper
{
    public const AGE_BRACKETS = [
        ['label' => 'Balita (0-5 tahun)', 'min' => 0, 'max' => 5],
        ['label' => 'Kanak-kanak (6-11 tahun)', 'min' => 6, 'max' => 11],
        ['label' => 'Remaja Awal (12-16 tahun)', 'min' => 12, 'max' => 16],
        ['label' => 'Remaja Akhir (17-25 tahun)', 'min' => 17, 'max' => 25],
        ['label' => 'Dewasa Awal (26-35 tahun)', 'min' => 26, 'max' => 35],
        ['label' => 'Dewasa Akhir (36-45 tahun)', 'min' => 36, 'max' => 45],
        ['label' => 'Lansia Awal (46-55 tahun)', 'min' => 46, 'max' => 55],
        ['label' => 'Lansia Akhir (56-65 tahun)', 'min' => 56, 'max' => 65],
        ['label' => 'Manula (>65 tahun)', 'min' => 66, 'max' => null],
    ];

    public const EDUCATION_BUCKETS = [
        'SD/Sederajat',
        'SMP/Sederajat',
        'SMA/Sederajat',
        'Diploma I/II',
        'Diploma III',
        'Diploma IV/Sarjana',
        'Magister',
        'Doktoral',
        'Lainnya / Belum Diisi',
    ];

    /**
     * Build SQL CASE WHEN statement for age aggregation
     */
    public static function buildAgeSqlCases(): string
    {
        $cases = [];
        foreach (self::AGE_BRACKETS as $index => $bracket) {
            $alias = 'age_' . $index;
            $min = $bracket['min'];
            $max = $bracket['max'];

            if ($max === null) {
                $cases[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, COALESCE(tanggal_lahir, birth_date), CURDATE()) >= {$min} THEN 1 ELSE 0 END) as {$alias}";
            } else {
                $cases[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, COALESCE(tanggal_lahir, birth_date), CURDATE()) BETWEEN {$min} AND {$max} THEN 1 ELSE 0 END) as {$alias}";
            }
        }

        return implode(', ', $cases);
    }

    /**
     * Format aggregated age results back to labels and data arrays
     */
    public static function formatAgeAggregation(object|array|null $result): array
    {
        $labels = [];
        $data = [];

        foreach (self::AGE_BRACKETS as $index => $bracket) {
            $labels[] = $bracket['label'];
            $alias = 'age_' . $index;
            $data[] = (int) (is_array($result) ? ($result[$alias] ?? 0) : ($result->{$alias} ?? 0));
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Group education records into buckets using SQL.
     * Note: We still use PHP for formatting since education buckets 
     * use complex regex/string matching that's hard to do purely in SQL reliably.
     * But we can just group by the raw strings in SQL and then map them.
     */
    public static function buildEducationSummary(iterable $rawEducations): array
    {
        $counts = array_fill_keys(self::EDUCATION_BUCKETS, 0);

        foreach ($rawEducations as $record) {
            $bucket = self::normalizeEducationBucket($record->pendidikan ?? '');
            $counts[$bucket] = ($counts[$bucket] ?? 0) + ($record->total ?? 1);
        }

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
        ];
    }

    public static function normalizeEducationBucket(?string $value): string
    {
        $normalized = Str::lower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?: '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?: '');

        if ($normalized === '') {
            return 'Lainnya / Belum Diisi';
        }

        if (Str::contains($normalized, ['s3', 'strata 3', 'doktor', 'doktoral', 'phd'])) {
            return 'Doktoral';
        }

        if (Str::contains($normalized, ['s2', 'strata 2', 'magister', 'master'])) {
            return 'Magister';
        }

        if (Str::contains($normalized, ['d4', 'd 4', 'd iv', 'diploma 4', 'diploma iv', 's1', 'strata 1', 'sarjana'])) {
            return 'Diploma IV/Sarjana';
        }

        if (Str::contains($normalized, ['d3', 'd 3', 'd iii', 'diploma 3', 'diploma iii'])) {
            return 'Diploma III';
        }

        if (Str::contains($normalized, ['d1', 'd 1', 'd i', 'diploma 1', 'diploma i', 'd2', 'd 2', 'd ii', 'diploma 2', 'diploma ii'])) {
            return 'Diploma I/II';
        }

        if (Str::contains($normalized, ['sma', 'smk', 'slta', 'madrasah aliyah', 'aliyah', 'paket c']) || preg_match('/\bma\b/', $normalized) === 1) {
            return 'SMA/Sederajat';
        }

        if (Str::contains($normalized, ['smp', 'sltp', 'madrasah tsanawiyah', 'tsanawiyah', 'paket b']) || preg_match('/\bmts\b/', $normalized) === 1) {
            return 'SMP/Sederajat';
        }

        if (Str::contains($normalized, ['sd', 'sekolah dasar', 'madrasah ibtidaiyah', 'ibtidaiyah', 'paket a']) || preg_match('/\bmi\b/', $normalized) === 1) {
            return 'SD/Sederajat';
        }

        return 'Lainnya / Belum Diisi';
    }
}
