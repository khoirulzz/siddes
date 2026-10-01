<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VisitorStatisticsService
{
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('visitor_statistics.timezone', 'Asia/Jakarta'));
    }

    public function identity(string $token, string $date): string
    {
        return hash_hmac('sha256', $date.'|'.$token, (string) config('app.key'));
    }

    public function record(string $date, string $visitorHash): void
    {
        DB::transaction(function () use ($date, $visitorHash): void {
            try {
                DB::table('website_visitor_days')->insert([
                    'visit_date' => $date,
                    'visitor_hash' => $visitorHash,
                ]);
            } catch (UniqueConstraintViolationException) {
                return;
            }

            try {
                DB::table('website_daily_stats')->insert(['visit_date' => $date, 'visitors' => 0]);
            } catch (UniqueConstraintViolationException) {
                // Another visitor has already initialized this day's aggregate.
            }

            DB::table('website_daily_stats')->where('visit_date', $date)->increment('visitors');
        }, 3);
    }

    public function summary(): ?array
    {
        if (! config('visitor_statistics.enabled')) {
            return null;
        }

        $today = $this->today();
        $load = static function () use ($today): array {
            $row = DB::table('website_daily_stats')->selectRaw(
                'COALESCE(SUM(CASE WHEN visit_date = ? THEN visitors ELSE 0 END), 0) AS today,
                 COALESCE(SUM(CASE WHEN visit_date = ? THEN visitors ELSE 0 END), 0) AS yesterday,
                 COALESCE(SUM(CASE WHEN visit_date >= ? AND visit_date <= ? THEN visitors ELSE 0 END), 0) AS month,
                 COALESCE(SUM(visitors), 0) AS total, MIN(visit_date) AS started_on',
                [$today->toDateString(), $today->subDay()->toDateString(),
                    $today->startOfMonth()->toDateString(), $today->toDateString()]
            )->first();

            return [
                'available' => true,
                'today' => (int) $row->today,
                'yesterday' => (int) $row->yesterday,
                'month' => (int) $row->month,
                'total' => (int) $row->total,
                'started_on' => $row->started_on,
            ];
        };

        try {
            return Cache::remember(
                'visitor-statistics:v1:'.app()->environment().':'.$today->toDateString(),
                (int) config('visitor_statistics.cache_seconds', 300),
                $load
            );
        } catch (Throwable $exception) {
            $this->logFailure('summary', $exception);

            try {
                return $load();
            } catch (Throwable) {
                return ['available' => false];
            }
        }
    }

    public function prune(int $batchSize = 500): int
    {
        $cutoff = $this->today()->subDays((int) config('visitor_statistics.retention_days', 35))->toDateString();
        $ids = DB::table('website_visitor_days')->where('visit_date', '<', $cutoff)
            ->orderBy('visit_date')->orderBy('id')->limit($batchSize)->pluck('id');

        return $ids->isEmpty() ? 0 : DB::table('website_visitor_days')->whereIn('id', $ids)->delete();
    }

    public function pruneOccasionally(): void
    {
        try {
            if (Cache::add('visitor-statistics:prune:'.app()->environment(), true, 3600)) {
                $this->prune();
            }
        } catch (Throwable $exception) {
            $this->logFailure('prune', $exception);
        }
    }

    public function logFailure(string $operation, Throwable $exception): void
    {
        try {
            if (Cache::add('visitor-statistics:error:'.$operation, true, 300)) {
                // Exception messages can contain SQL bindings; only log the class.
                Log::warning('Visitor statistics unavailable', [
                    'operation' => $operation,
                    'exception' => $exception::class,
                ]);
            }
        } catch (Throwable) {
            // Statistics must never prevent the public page from loading.
        }
    }
}
