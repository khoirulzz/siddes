<?php

namespace App\Services;

use App\Exceptions\PopulationImportException;

final class PopulationImportMemory
{
    private static ?int $baseline = null;

    public static function begin(): void
    {
        self::$baseline = memory_get_usage(true);
    }

    public static function check(): void
    {
        self::$baseline ??= memory_get_usage(true);
        $limit = trim((string) ini_get('memory_limit'));
        $bytes = (int) $limit;
        $bytes *= match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };
        // Keep headroom for PHP errors while bounding memory added by this import request.
        $phpBudget = $bytes > 0 ? max(0, $bytes - 48 * 1024 * 1024) : PHP_INT_MAX;
        $requestBudget = self::$baseline + 128 * 1024 * 1024;
        $budget = min($phpBudget, $requestBudget);
        if (memory_get_usage(true) >= $budget) {
            throw new PopulationImportException('Pemeriksaan dihentikan karena mendekati batas memori server. Pecah file menjadi beberapa bagian yang lebih kecil lalu periksa kembali. Belum ada data penduduk yang disimpan.', 507);
        }
    }
}
