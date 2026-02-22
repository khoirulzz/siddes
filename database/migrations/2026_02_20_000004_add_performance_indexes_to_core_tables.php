<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('population_records', 'population_records_no_kk_idx', ['no_kk']);
        $this->addIndexIfMissing('population_records', 'population_records_nkk_idx', ['nkk']);
        $this->addIndexIfMissing('population_records', 'population_records_dusun_idx', ['dusun']);
        $this->addIndexIfMissing('population_records', 'population_records_hamlet_idx', ['hamlet']);
        $this->addIndexIfMissing('population_records', 'population_records_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('letter_service_requests', 'letter_service_requests_official_number_idx', ['official_number']);
        $this->addIndexIfMissing('letter_service_requests', 'letter_service_requests_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('pbb_payment_requests', 'pbb_payment_requests_ticket_code_idx', ['ticket_code']);
        $this->addIndexIfMissing('pbb_payment_requests', 'pbb_payment_requests_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('complaint_reports', 'complaint_reports_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('land_records', 'land_records_hamlet_idx', ['hamlet']);
        $this->addIndexIfMissing('land_records', 'land_records_category_idx', ['category']);
        $this->addIndexIfMissing('land_records', 'land_records_status_idx', ['status']);
        $this->addIndexIfMissing('land_records', 'land_records_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('village_activities', 'village_activities_status_idx', ['status']);
        $this->addIndexIfMissing('village_activities', 'village_activities_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('news', 'news_published_at_idx', ['is_published', 'published_at']);
        $this->addIndexIfMissing('news', 'news_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('announcements', 'announcements_active_period_idx', ['is_active', 'start_date', 'end_date']);
        $this->addIndexIfMissing('announcements', 'announcements_created_at_idx', ['created_at']);

        $this->addIndexIfMissing('galleries', 'galleries_activity_date_idx', ['activity_date']);
        $this->addIndexIfMissing('galleries', 'galleries_created_at_idx', ['created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('population_records', 'population_records_no_kk_idx');
        $this->dropIndexIfExists('population_records', 'population_records_nkk_idx');
        $this->dropIndexIfExists('population_records', 'population_records_dusun_idx');
        $this->dropIndexIfExists('population_records', 'population_records_hamlet_idx');
        $this->dropIndexIfExists('population_records', 'population_records_created_at_idx');

        $this->dropIndexIfExists('letter_service_requests', 'letter_service_requests_official_number_idx');
        $this->dropIndexIfExists('letter_service_requests', 'letter_service_requests_created_at_idx');

        $this->dropIndexIfExists('pbb_payment_requests', 'pbb_payment_requests_ticket_code_idx');
        $this->dropIndexIfExists('pbb_payment_requests', 'pbb_payment_requests_created_at_idx');

        $this->dropIndexIfExists('complaint_reports', 'complaint_reports_created_at_idx');

        $this->dropIndexIfExists('land_records', 'land_records_hamlet_idx');
        $this->dropIndexIfExists('land_records', 'land_records_category_idx');
        $this->dropIndexIfExists('land_records', 'land_records_status_idx');
        $this->dropIndexIfExists('land_records', 'land_records_created_at_idx');

        $this->dropIndexIfExists('village_activities', 'village_activities_status_idx');
        $this->dropIndexIfExists('village_activities', 'village_activities_created_at_idx');

        $this->dropIndexIfExists('news', 'news_published_at_idx');
        $this->dropIndexIfExists('news', 'news_created_at_idx');

        $this->dropIndexIfExists('announcements', 'announcements_active_period_idx');
        $this->dropIndexIfExists('announcements', 'announcements_created_at_idx');

        $this->dropIndexIfExists('galleries', 'galleries_activity_date_idx');
        $this->dropIndexIfExists('galleries', 'galleries_created_at_idx');
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if ($this->indexExists($table, $indexName, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    /**
     * @param array<int, string> $columns
     */
    private function indexExists(string $table, string $indexName, array $columns = []): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return false;
        }

        $rows = DB::select('SHOW INDEX FROM `' . $table . '`');
        if ($rows === []) {
            return false;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $keyName = (string) ($row->Key_name ?? '');
            if ($keyName === '') {
                continue;
            }

            if ($keyName === $indexName) {
                return true;
            }

            if ($keyName === 'PRIMARY') {
                continue;
            }

            $sequence = max(((int) ($row->Seq_in_index ?? 1)) - 1, 0);
            $grouped[$keyName][$sequence] = (string) ($row->Column_name ?? '');
        }

        if ($columns === []) {
            return false;
        }

        $target = array_values($columns);
        foreach ($grouped as $indexedColumns) {
            ksort($indexedColumns);
            if (array_values($indexedColumns) === $target) {
                return true;
            }
        }

        return false;
    }
};
