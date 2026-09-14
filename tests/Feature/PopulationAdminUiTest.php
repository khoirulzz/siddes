<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationAdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_population_index_defaults_to_household_view(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('dashboard.population-records.index'))
            ->assertOk()
            ->assertSee('Daftar Kartu Keluarga')
            ->assertSee('Import Excel atau CSV');
    }

    public function test_household_detail_has_member_and_edit_actions(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $household = Household::query()->create([
            'no_kk' => '3326010101010001', 'nama_kepala_keluarga' => 'Budi Santoso',
            'dusun' => 'Bojongireng', 'rt' => '001', 'rw' => '002',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.population-households.show', $household))
            ->assertOk()
            ->assertSee('Tambah Anggota')
            ->assertSee('Edit Data KK')
            ->assertSee('3326010101010001');
    }

    public function test_population_index_links_each_household_row_to_its_own_detail(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $first = Household::query()->create([
            'no_kk' => '3326010101010001', 'nama_kepala_keluarga' => 'Budi Santoso',
            'dusun' => 'Bojongireng',
        ]);
        $second = Household::query()->create([
            'no_kk' => '3326010101010002', 'nama_kepala_keluarga' => 'Siti Aminah',
            'dusun' => 'Bojongireng',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.population-records.index'))
            ->assertOk()
            ->assertSee('data-row-link="'.route('dashboard.population-households.show', $first).'"', false)
            ->assertSee('data-row-link="'.route('dashboard.population-households.show', $second).'"', false);
    }

    public function test_individual_tab_renders_without_a_household_loop_variable(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)
            ->get(route('dashboard.population-records.index', ['view' => 'individual']))
            ->assertOk()
            ->assertSee('Daftar Penduduk')
            ->assertSee('Import Excel atau CSV')
            ->assertDontSee('data-row-link="', false);
    }

    public function test_statistics_are_served_from_the_lazy_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->getJson(route('dashboard.population-records.statistics'))
            ->assertOk()
            ->assertJsonStructure([
                'hamlets' => ['labels', 'data'],
                'genders' => ['labels', 'data'],
                'ages' => ['labels', 'data'],
                'education' => ['labels', 'data'],
            ]);
    }

    public function test_xlsx_and_csv_templates_are_available(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)
            ->get(route('dashboard.population-records.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)
            ->get(route('dashboard.population-records.template', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
