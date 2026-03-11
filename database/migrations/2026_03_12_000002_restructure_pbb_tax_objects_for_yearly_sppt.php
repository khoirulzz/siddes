<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pbb_tax_objects')) {
            return;
        }

        Schema::table('pbb_tax_objects', function (Blueprint $table): void {
            if (! Schema::hasColumn('pbb_tax_objects', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('tax_name');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'location')) {
                $table->string('location')->nullable()->after('owner_name');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'land_area')) {
                $table->decimal('land_area', 12, 2)->nullable()->after('location');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'building_area')) {
                $table->decimal('building_area', 12, 2)->nullable()->after('land_area');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'nama_wp_sppt')) {
                $table->string('nama_wp_sppt')->nullable()->after('nop');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'jalan_wp_sppt')) {
                $table->string('jalan_wp_sppt')->nullable()->after('nama_wp_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'rt_wp_sppt')) {
                $table->string('rt_wp_sppt', 10)->nullable()->after('jalan_wp_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'rw_wp_sppt')) {
                $table->string('rw_wp_sppt', 10)->nullable()->after('rt_wp_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'desa_wp_sppt')) {
                $table->string('desa_wp_sppt', 150)->nullable()->after('rw_wp_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'jalan_op_sppt')) {
                $table->string('jalan_op_sppt')->nullable()->after('desa_wp_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'rt_op_sppt')) {
                $table->string('rt_op_sppt', 10)->nullable()->after('jalan_op_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'rw_op_sppt')) {
                $table->string('rw_op_sppt', 10)->nullable()->after('rt_op_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'luas_tanah_sppt')) {
                $table->decimal('luas_tanah_sppt', 12, 2)->nullable()->after('rw_op_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'luas_bangunan_sppt')) {
                $table->decimal('luas_bangunan_sppt', 12, 2)->nullable()->after('luas_tanah_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'pbb_terhutang')) {
                $table->decimal('pbb_terhutang', 14, 2)->nullable()->after('luas_bangunan_sppt');
            }
            if (! Schema::hasColumn('pbb_tax_objects', 'tanggal_pembayaran')) {
                $table->date('tanggal_pembayaran')->nullable()->after('pbb_terhutang');
            }
        });

        $columns = Schema::getColumnListing('pbb_tax_objects');
        $has = static fn (string $column): bool => in_array($column, $columns, true);

        DB::table('pbb_tax_objects')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($has): void {
                foreach ($rows as $row) {
                    $owner = self::pickFirstNonEmpty([
                        $has('nama_wp_sppt') ? ($row->nama_wp_sppt ?? null) : null,
                        $has('owner_name') ? ($row->owner_name ?? null) : null,
                        $has('tax_name') ? ($row->tax_name ?? null) : null,
                    ]);

                    $jalanWp = self::pickFirstNonEmpty([
                        $has('jalan_wp_sppt') ? ($row->jalan_wp_sppt ?? null) : null,
                        $has('tax_address') ? ($row->tax_address ?? null) : null,
                        $has('location') ? ($row->location ?? null) : null,
                    ]);

                    $jalanOp = self::pickFirstNonEmpty([
                        $has('jalan_op_sppt') ? ($row->jalan_op_sppt ?? null) : null,
                        $has('location') ? ($row->location ?? null) : null,
                        $jalanWp,
                    ]);

                    $luasTanah = $has('luas_tanah_sppt') ? ($row->luas_tanah_sppt ?? null) : null;
                    if ($luasTanah === null && $has('land_area')) {
                        $luasTanah = $row->land_area ?? null;
                    }

                    $luasBangunan = $has('luas_bangunan_sppt') ? ($row->luas_bangunan_sppt ?? null) : null;
                    if ($luasBangunan === null && $has('building_area')) {
                        $luasBangunan = $row->building_area ?? null;
                    }

                    $terhutang = $has('pbb_terhutang') ? ($row->pbb_terhutang ?? null) : null;
                    if ($terhutang === null && $has('amount_due')) {
                        $terhutang = $row->amount_due ?? null;
                    }

                    $updates = [];
                    if ($has('nama_wp_sppt')) {
                        $updates['nama_wp_sppt'] = $owner;
                    }
                    if ($has('jalan_wp_sppt')) {
                        $updates['jalan_wp_sppt'] = $jalanWp;
                    }
                    if ($has('desa_wp_sppt')) {
                        $updates['desa_wp_sppt'] = self::pickFirstNonEmpty([
                            $row->desa_wp_sppt ?? null,
                            'Desa Lambanggelun',
                        ]);
                    }
                    if ($has('jalan_op_sppt')) {
                        $updates['jalan_op_sppt'] = $jalanOp;
                    }
                    if ($has('luas_tanah_sppt')) {
                        $updates['luas_tanah_sppt'] = $luasTanah;
                    }
                    if ($has('luas_bangunan_sppt')) {
                        $updates['luas_bangunan_sppt'] = $luasBangunan;
                    }
                    if ($has('pbb_terhutang')) {
                        $updates['pbb_terhutang'] = $terhutang;
                    }
                    if ($has('tax_name')) {
                        $updates['tax_name'] = self::pickFirstNonEmpty([$row->tax_name ?? null, $owner]);
                    }
                    if ($has('owner_name')) {
                        $updates['owner_name'] = self::pickFirstNonEmpty([$row->owner_name ?? null, $owner]);
                    }
                    if ($has('location')) {
                        $updates['location'] = self::pickFirstNonEmpty([$row->location ?? null, $jalanOp, $jalanWp]);
                    }
                    if ($has('tax_address')) {
                        $updates['tax_address'] = self::pickFirstNonEmpty([$row->tax_address ?? null, $jalanWp, $jalanOp]);
                    }
                    if ($has('land_area')) {
                        $updates['land_area'] = self::pickFirstNonEmpty([$row->land_area ?? null, $luasTanah, 0]);
                    }
                    if ($has('building_area')) {
                        $updates['building_area'] = self::pickFirstNonEmpty([$row->building_area ?? null, $luasBangunan, 0]);
                    }
                    if ($has('amount_due')) {
                        $updates['amount_due'] = self::pickFirstNonEmpty([$row->amount_due ?? null, $terhutang, 0]);
                    }
                    if ($has('tax_year')) {
                        $updates['tax_year'] = self::pickFirstNonEmpty([$row->tax_year ?? null, 2026]);
                    }
                    if ($has('status')) {
                        $status = $row->status ?? null;
                        if (in_array($status, ['Belum Lunas', 'Lunas'], true)) {
                            $updates['status'] = $status;
                        } else {
                            $updates['status'] = ($row->tanggal_pembayaran ?? null) ? 'Lunas' : 'Belum Lunas';
                        }
                    }

                    DB::table('pbb_tax_objects')->where('id', $row->id)->update($updates);
                }
            });

        $this->dropIndexIfExists('pbb_tax_objects', 'pbb_tax_objects_nop_unique');
        $this->dropIndexIfExists('pbb_tax_objects', 'pbb_tax_objects_nop_year_unique');

        if (! $this->indexExists('pbb_tax_objects', 'pbb_tax_objects_nop_year_unique')) {
            Schema::table('pbb_tax_objects', function (Blueprint $table): void {
                $table->unique(['nop', 'tax_year'], 'pbb_tax_objects_nop_year_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pbb_tax_objects')) {
            return;
        }

        $this->dropIndexIfExists('pbb_tax_objects', 'pbb_tax_objects_nop_year_unique');

        Schema::table('pbb_tax_objects', function (Blueprint $table): void {
            foreach ([
                'nama_wp_sppt',
                'jalan_wp_sppt',
                'rt_wp_sppt',
                'rw_wp_sppt',
                'desa_wp_sppt',
                'jalan_op_sppt',
                'rt_op_sppt',
                'rw_op_sppt',
                'luas_tanah_sppt',
                'luas_bangunan_sppt',
                'pbb_terhutang',
                'tanggal_pembayaran',
            ] as $column) {
                if (Schema::hasColumn('pbb_tax_objects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table) || Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $indexes = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);
        return $indexes !== [];
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    /**
     * @param array<int, mixed> $values
     */
    private static function pickFirstNonEmpty(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return $value;
        }

        return null;
    }
};
