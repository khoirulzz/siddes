<?php

use App\Services\VisitorStatisticsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('visitor-statistics:prune', function (VisitorStatisticsService $statistics) {
    $deleted = $statistics->prune();
    $this->info("Dihapus {$deleted} detail pengunjung lama; rekap kunjungan tetap tersimpan.");
})->purpose('Hapus maksimal 500 detail deduplikasi pengunjung yang melewati masa retensi');
