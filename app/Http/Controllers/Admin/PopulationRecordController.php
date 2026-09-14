<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\PopulationImportRun;
use App\Models\PopulationRecord;
use App\Services\PopulationHouseholdSyncService;
use App\Support\PopulationStatHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class PopulationRecordController extends Controller
{
    public function __construct(
        private readonly PopulationHouseholdSyncService $householdSync,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $viewMode = $request->query('view') === 'individual' ? 'individual' : 'kk';
        $selectedHamlet = $request->string('hamlet')->toString() ?: 'Semua';
        if ($selectedHamlet !== 'Semua' && ! in_array($selectedHamlet, PopulationRecord::HAMLETS, true)) {
            $selectedHamlet = 'Semua';
        }
        $search = trim((string) $request->query('q', ''));

        $residentStatsQuery = PopulationRecord::query()->inHamlet($selectedHamlet);
        $this->applySearchFilter($residentStatsQuery, $search);

        $filteredTotal = (clone $residentStatsQuery)->count();
        $genderByHamlet = (clone $residentStatsQuery)
            ->selectRaw('COALESCE(jenis_kelamin, gender) as gender_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(jenis_kelamin, gender)')
            ->pluck('total', 'gender_name');

        $householdsQuery = Household::query()
            ->withCount([
                'currentMembers as total_members' => function ($query): void {
                    $query->where('is_current', true);
                },
            ]);

        if ($selectedHamlet !== 'Semua') {
            $householdsQuery->where('dusun', $selectedHamlet);
        }

        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search) ?: $search;
            $householdsQuery->where(function ($query) use ($search, $digits): void {
                $query->where('no_kk', 'like', '%' . $digits . '%')
                    ->orWhere('nama_kepala_keluarga', 'like', '%' . $search . '%')
                    ->orWhere('dusun', 'like', '%' . $search . '%')
                    ->orWhereHas('currentMembers.resident', function ($residentQuery) use ($search, $digits): void {
                        $residentQuery->where('nik', 'like', '%' . $digits . '%')
                            ->orWhere('nama_lengkap', 'like', '%' . $search . '%')
                            ->orWhere('full_name', 'like', '%' . $search . '%');
                    });
            });
        }

        $filteredHouseholdTotal = (clone $householdsQuery)->count();
        $records = null;
        $households = null;

        if ($viewMode === 'individual') {
            $records = PopulationRecord::query()
                ->with(['currentMembership.household'])
                ->inHamlet($selectedHamlet);
            $this->applySearchFilter($records, $search);
            $records = $records
                ->orderByRaw('COALESCE(dusun, hamlet)')
                ->orderByRaw('COALESCE(nama_lengkap, full_name)')
                ->paginate(40)
                ->withQueryString();
        } else {
            $households = $householdsQuery
                ->orderByDesc('updated_at')
                ->orderBy('no_kk')
                ->paginate(30, ['*'], 'kk_page')
                ->withQueryString();
        }

        return view('dashboard.population.index', [
            'items' => $records,
            'households' => $households,
            'viewMode' => $viewMode,
            'filteredTotal' => $filteredTotal,
            'filteredHouseholdTotal' => $filteredHouseholdTotal,
            'hamlets' => PopulationRecord::HAMLETS,
            'selectedHamlet' => $selectedHamlet,
            'filters' => [
                'q' => $search,
            ],
            'genderSummary' => [
                'Laki-laki' => (int) ($genderByHamlet['Laki-laki'] ?? 0),
                'Perempuan' => (int) ($genderByHamlet['Perempuan'] ?? 0),
            ],
            'recentImports' => PopulationImportRun::query()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $selectedHamlet = $request->string('hamlet')->toString() ?: 'Semua';
        if ($selectedHamlet !== 'Semua' && ! in_array($selectedHamlet, PopulationRecord::HAMLETS, true)) {
            $selectedHamlet = 'Semua';
        }
        $search = trim((string) $request->query('q', ''));
        $query = PopulationRecord::query()->inHamlet($selectedHamlet);
        $this->applySearchFilter($query, $search);

        $hamlets = (clone $query)
            ->selectRaw('COALESCE(dusun, hamlet) as hamlet_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(dusun, hamlet)')
            ->orderByRaw('COALESCE(dusun, hamlet)')
            ->get();
        $genders = (clone $query)
            ->selectRaw('COALESCE(jenis_kelamin, gender) as gender_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(jenis_kelamin, gender)')
            ->pluck('total', 'gender_name');
        $ageSummary = PopulationStatHelper::formatAgeAggregation(
            (clone $query)->selectRaw(PopulationStatHelper::buildAgeSqlCases())->first(),
        );
        $educationSummary = PopulationStatHelper::buildEducationSummary(
            (clone $query)->selectRaw('pendidikan, COUNT(*) as total')->groupBy('pendidikan')->get(),
        );

        return response()->json([
            'hamlets' => ['labels' => $hamlets->pluck('hamlet_name'), 'data' => $hamlets->pluck('total')->map(fn ($total): int => (int) $total)],
            'genders' => ['labels' => ['Laki-laki', 'Perempuan'], 'data' => [(int) ($genders['Laki-laki'] ?? 0), (int) ($genders['Perempuan'] ?? 0)]],
            'ages' => $ageSummary,
            'education' => $educationSummary,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $prefillHousehold = $request->integer('household')
            ? Household::query()->findOrFail($request->integer('household'))
            : null;
        $formMode = $prefillHousehold ? 'member' : ($request->query('mode') === 'household' ? 'household' : 'resident');
        $title = match ($formMode) {
            'household' => 'Tambah KK Baru',
            'member' => 'Tambah Anggota Keluarga',
            default => 'Tambah Penduduk',
        };

        return view('dashboard.population.form', [
            'item' => new PopulationRecord(),
            'method' => 'POST',
            'route' => route('dashboard.population-records.store'),
            'title' => $title,
            'formMode' => $formMode,
            'prefillHousehold' => $prefillHousehold,
            'hamlets' => PopulationRecord::HAMLETS,
            'statusPerkawinanOptions' => PopulationRecord::STATUS_PERKAWINAN_OPTIONS,
            'statusHubunganOptions' => PopulationRecord::STATUS_HUBUNGAN_OPTIONS,
            'golonganDarahOptions' => PopulationRecord::GOLONGAN_DARAH_OPTIONS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);

        $resident = PopulationRecord::create($this->extractResidentPayload($payload));
        $household = $this->householdSync->sync($resident, $payload);

        if ($request->filled('context_household_id')) {
            return redirect()
                ->route('dashboard.population-households.show', $household)
                ->with('success', 'Anggota keluarga berhasil ditambahkan.');
        }

        return redirect()->route('dashboard.population-records.index')->with('success', 'Data kependudukan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, PopulationRecord $populationRecord)
    {
        $populationRecord->loadMissing(['currentMembership.household']);
        $household = $populationRecord->currentMembership?->household;

        if ($household) {
            $populationRecord->setAttribute('no_kk', $household->no_kk);
            $populationRecord->setAttribute('nama_kepala_keluarga', $household->nama_kepala_keluarga);
            $populationRecord->setAttribute('alamat', $household->alamat);
        }

        return view('dashboard.population.form', [
            'item' => $populationRecord,
            'method' => 'PUT',
            'route' => route('dashboard.population-records.update', $populationRecord),
            'title' => 'Edit Data Kependudukan',
            'formMode' => 'edit',
            'prefillHousehold' => $household,
            'contextHouseholdContext' => $request->integer('household') ?: null,
            'hamlets' => PopulationRecord::HAMLETS,
            'statusPerkawinanOptions' => PopulationRecord::STATUS_PERKAWINAN_OPTIONS,
            'statusHubunganOptions' => PopulationRecord::STATUS_HUBUNGAN_OPTIONS,
            'golonganDarahOptions' => PopulationRecord::GOLONGAN_DARAH_OPTIONS,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PopulationRecord $populationRecord)
    {
        $payload = $this->validatePayload($request);

        $populationRecord->update($this->extractResidentPayload($payload));
        $household = $this->householdSync->sync($populationRecord, $payload);

        if ($request->integer('context_household_id') === $household->id) {
            return redirect()
                ->route('dashboard.population-households.show', $household)
                ->with('success', 'Data penduduk berhasil diperbarui.');
        }

        return redirect()->route('dashboard.population-records.index')->with('success', 'Data kependudukan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PopulationRecord $populationRecord)
    {
        $householdIds = $populationRecord->householdMemberships()->pluck('household_id')->all();
        $returnHouseholdId = $request->integer('context_household_id');
        $populationRecord->delete();
        $this->householdSync->cleanupEmptyHouseholds($householdIds);

        if ($returnHouseholdId && Household::query()->whereKey($returnHouseholdId)->exists()) {
            return redirect()
                ->route('dashboard.population-households.show', $returnHouseholdId)
                ->with('success', 'Data penduduk berhasil dihapus.');
        }

        return redirect()->route('dashboard.population-records.index')->with('success', 'Data kependudukan berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('population_records', 'nik')->ignore($request->route('population_record')),
            ],
            'no_kk' => ['required', 'digits:16'],
            'nama_kepala_keluarga' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'kode_pos' => ['nullable', 'digits_between:4,10'],
            'dusun' => ['required', Rule::in(PopulationRecord::HAMLETS)],
            'desa' => ['nullable', 'string', 'max:120'],
            'kecamatan' => ['nullable', 'string', 'max:120'],
            'kabupaten' => ['nullable', 'string', 'max:120'],
            'provinsi' => ['nullable', 'string', 'max:120'],
            'no_urut_kk' => ['nullable', 'integer', 'min:1', 'max:999'],
            'status_hubungan' => ['required', Rule::in(PopulationRecord::STATUS_HUBUNGAN_OPTIONS)],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'agama' => ['required', 'string', 'max:255'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'jenis_pekerjaan' => ['required', 'string', 'max:255'],
            'status_perkawinan' => ['required', Rule::in(PopulationRecord::STATUS_PERKAWINAN_OPTIONS)],
            'kewarganegaraan' => ['required', Rule::in(['WNI', 'WNA'])],
            'no_paspor' => ['nullable', 'string', 'max:80'],
            'no_kitas_kitap' => ['nullable', 'string', 'max:80'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'golongan_darah' => ['nullable', Rule::in(PopulationRecord::GOLONGAN_DARAH_OPTIONS)],
            'context_household_id' => ['nullable', 'integer', 'exists:households,id'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if (($request->input('kewarganegaraan') === 'WNA')
                && ! $request->filled('no_paspor')
                && ! $request->filled('no_kitas_kitap')) {
                $validator->errors()->add('no_paspor', 'Untuk WNA, isi minimal nomor paspor atau nomor KITAS/KITAP.');
            }
        });

        $validated = $validator->validate();
        $validated['full_name'] = $validated['nama_lengkap'];
        $validated['birth_place'] = $validated['tempat_lahir'];
        $validated['birth_date'] = $validated['tanggal_lahir'];
        $validated['gender'] = $validated['jenis_kelamin'];
        $validated['hamlet'] = $validated['dusun'];
        $validated['religion'] = $validated['agama'];
        $validated['occupation'] = $validated['jenis_pekerjaan'];
        $validated['pekerjaan'] = $validated['jenis_pekerjaan'];
        $validated['nkk'] = $validated['no_kk'];
        $validated['desa'] = $validated['desa'] ?: PopulationRecord::DEFAULT_VILLAGE;
        $validated['kecamatan'] = $validated['kecamatan'] ?: PopulationRecord::DEFAULT_DISTRICT;
        $validated['kabupaten'] = $validated['kabupaten'] ?: PopulationRecord::DEFAULT_REGENCY;
        $validated['provinsi'] = $validated['provinsi'] ?: PopulationRecord::DEFAULT_PROVINCE;
        $validated['kode_pos'] = $validated['kode_pos'] ?: PopulationRecord::DEFAULT_POSTAL_CODE;
        $validated['rt'] = $validated['rt'] ? str_pad((string) $validated['rt'], 3, '0', STR_PAD_LEFT) : null;
        $validated['rw'] = $validated['rw'] ? str_pad((string) $validated['rw'], 3, '0', STR_PAD_LEFT) : null;
        $validated['address_detail'] = $validated['alamat'] ?: null;
        $validated['nama_kepala_keluarga'] = $validated['nama_kepala_keluarga'] ?: (
            $validated['status_hubungan'] === 'Kepala Keluarga' ? $validated['nama_lengkap'] : null
        );

        return $validated;
    }

    private function extractResidentPayload(array $payload): array
    {
        return [
            'nama_lengkap' => $payload['nama_lengkap'],
            'full_name' => $payload['full_name'],
            'nik' => $payload['nik'],
            'no_kk' => $payload['no_kk'],
            'nkk' => $payload['nkk'],
            'jenis_kelamin' => $payload['jenis_kelamin'],
            'gender' => $payload['gender'],
            'tempat_lahir' => $payload['tempat_lahir'],
            'birth_place' => $payload['birth_place'],
            'tanggal_lahir' => $payload['tanggal_lahir'],
            'birth_date' => $payload['birth_date'],
            'agama' => $payload['agama'],
            'religion' => $payload['religion'],
            'pendidikan' => $payload['pendidikan'],
            'jenis_pekerjaan' => $payload['jenis_pekerjaan'],
            'pekerjaan' => $payload['pekerjaan'],
            'occupation' => $payload['occupation'],
            'status_perkawinan' => $payload['status_perkawinan'],
            'status_hubungan' => $payload['status_hubungan'],
            'kewarganegaraan' => $payload['kewarganegaraan'],
            'no_paspor' => $payload['no_paspor'] ?: null,
            'no_kitas_kitap' => $payload['no_kitas_kitap'] ?: null,
            'nama_ayah' => $payload['nama_ayah'] ?: null,
            'nama_ibu' => $payload['nama_ibu'] ?: null,
            'golongan_darah' => $payload['golongan_darah'] ?: null,
            'rt' => $payload['rt'] ?: null,
            'rw' => $payload['rw'] ?: null,
            'dusun' => $payload['dusun'],
            'hamlet' => $payload['hamlet'],
            'desa' => $payload['desa'],
            'kecamatan' => $payload['kecamatan'],
            'kabupaten' => $payload['kabupaten'],
            'provinsi' => $payload['provinsi'],
            'kode_pos' => $payload['kode_pos'],
            'address_detail' => $payload['address_detail'],
        ];
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $search) ?: $search;

        $query->where(function (Builder $builder) use ($digits, $search): void {
            $builder->where('nik', 'like', '%' . $digits . '%')
                ->orWhere('no_kk', 'like', '%' . $digits . '%')
                ->orWhere('nkk', 'like', '%' . $digits . '%')
                ->orWhere('nama_lengkap', 'like', '%' . $search . '%')
                ->orWhere('full_name', 'like', '%' . $search . '%');
        });
    }

}
