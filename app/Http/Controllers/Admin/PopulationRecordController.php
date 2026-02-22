<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PopulationRecordsImport;
use App\Models\PopulationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PopulationRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedHamlet = $request->string('hamlet')->toString() ?: 'Semua';
        $search = trim((string) $request->query('q', ''));

        $baseQuery = PopulationRecord::query()->inHamlet($selectedHamlet);
        $this->applySearchFilter($baseQuery, $search);

        $records = (clone $baseQuery)
            ->orderByRaw('COALESCE(dusun, hamlet)')
            ->orderByRaw('COALESCE(nama_lengkap, full_name)')
            ->paginate(50)
            ->withQueryString();

        $filteredTotal = (clone $baseQuery)->count();

        $summaryByHamlet = (clone $baseQuery)
            ->selectRaw('COALESCE(dusun, hamlet) as hamlet_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(dusun, hamlet)')
            ->orderByRaw('COALESCE(dusun, hamlet)')
            ->get();

        $genderByHamlet = (clone $baseQuery)
            ->selectRaw('COALESCE(jenis_kelamin, gender) as gender_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(jenis_kelamin, gender)')
            ->pluck('total', 'gender_name');

        return view('dashboard.population.index', [
            'items' => $records,
            'filteredTotal' => $filteredTotal,
            'hamlets' => PopulationRecord::HAMLETS,
            'selectedHamlet' => $selectedHamlet,
            'filters' => [
                'q' => $search,
            ],
            'summaryByHamlet' => $summaryByHamlet,
            'genderSummary' => [
                'Laki-laki' => (int) ($genderByHamlet['Laki-laki'] ?? 0),
                'Perempuan' => (int) ($genderByHamlet['Perempuan'] ?? 0),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.population.form', [
            'item' => new PopulationRecord(),
            'method' => 'POST',
            'route' => route('dashboard.population-records.store'),
            'title' => 'Tambah Data Kependudukan',
            'hamlets' => PopulationRecord::HAMLETS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        PopulationRecord::create($this->validatePayload($request));

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
    public function edit(PopulationRecord $populationRecord)
    {
        return view('dashboard.population.form', [
            'item' => $populationRecord,
            'method' => 'PUT',
            'route' => route('dashboard.population-records.update', $populationRecord),
            'title' => 'Edit Data Kependudukan',
            'hamlets' => PopulationRecord::HAMLETS,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PopulationRecord $populationRecord)
    {
        $populationRecord->update($this->validatePayload($request));

        return redirect()->route('dashboard.population-records.index')->with('success', 'Data kependudukan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PopulationRecord $populationRecord)
    {
        $populationRecord->delete();

        return redirect()->route('dashboard.population-records.index')->with('success', 'Data kependudukan berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => [
                'required',
                'digits_between:8,20',
                Rule::unique('population_records', 'nik')->ignore($request->route('population_record')),
            ],
            'no_kk' => ['required', 'digits_between:8,20'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'agama' => ['required', 'string', 'max:255'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'pekerjaan' => ['required', 'string', 'max:255'],
            'status_perkawinan' => ['nullable', 'string', 'max:255'],
            'kewarganegaraan' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'dusun' => ['required', Rule::in(PopulationRecord::HAMLETS)],
            'desa' => ['nullable', 'string', 'max:120'],
            'kecamatan' => ['nullable', 'string', 'max:120'],
            'kabupaten' => ['nullable', 'string', 'max:120'],
            'provinsi' => ['nullable', 'string', 'max:120'],
            'kode_pos' => ['nullable', 'string', 'max:12'],
            'address_detail' => ['nullable', 'string'],
        ]);

        $validated['full_name'] = $validated['nama_lengkap'];
        $validated['nkk'] = $validated['no_kk'];
        $validated['birth_place'] = $validated['tempat_lahir'];
        $validated['birth_date'] = $validated['tanggal_lahir'];
        $validated['gender'] = $validated['jenis_kelamin'];
        $validated['hamlet'] = $validated['dusun'];
        $validated['religion'] = $validated['agama'];
        $validated['occupation'] = $validated['pekerjaan'];
        $validated['desa'] = $validated['desa'] ?: PopulationRecord::DEFAULT_VILLAGE;
        $validated['kecamatan'] = $validated['kecamatan'] ?: PopulationRecord::DEFAULT_DISTRICT;
        $validated['kabupaten'] = $validated['kabupaten'] ?: PopulationRecord::DEFAULT_REGENCY;
        $validated['provinsi'] = $validated['provinsi'] ?: PopulationRecord::DEFAULT_PROVINCE;
        $validated['kode_pos'] = $validated['kode_pos'] ?: PopulationRecord::DEFAULT_POSTAL_CODE;
        $validated['kewarganegaraan'] = $validated['kewarganegaraan'] ?: 'WNI';

        return $validated;
    }

    public function import(Request $request)
    {
        $payload = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
            'hamlet_override' => ['nullable', Rule::in(PopulationRecord::HAMLETS)],
        ]);

        $import = new PopulationRecordsImport(
            $payload['hamlet_override'] ?? null,
            $request->file('file')->getClientOriginalName(),
        );

        Excel::import($import, $request->file('file'));

        $summary = $import->summary();
        $message = "Import selesai: {$summary['inserted']} data masuk, {$summary['duplicates']} duplikat NIK dilewati, {$summary['skipped']} baris tidak valid.";

        return redirect()->route('dashboard.population-records.index')->with('success', $message);
    }

    public function template()
    {
        $columns = [
            'nik',
            'no_kk',
            'nama_lengkap',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'agama',
            'pendidikan',
            'pekerjaan',
            'status_perkawinan',
            'kewarganegaraan',
            'rt',
            'rw',
            'dusun',
            'desa',
            'kecamatan',
            'kabupaten',
            'provinsi',
            'kode_pos',
            'alamat_lengkap',
        ];

        return response()->streamDownload(function () use ($columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            fclose($handle);
        }, 'template-kependudukan.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $search) ?: $search;

        $query->where(function (Builder $builder) use ($digits): void {
            $builder->where('nik', 'like', '%' . $digits . '%')
                ->orWhere('no_kk', 'like', '%' . $digits . '%')
                ->orWhere('nkk', 'like', '%' . $digits . '%');
        });
    }
}
