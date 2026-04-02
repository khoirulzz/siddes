<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PbbTaxObjectsImport;
use App\Models\PbbTaxObject;
use App\Support\SpreadsheetImportHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PbbTaxObjectController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $year = $request->filled('year') ? (int) $request->query('year') : null;

        $taxObjects = PbbTaxObject::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $digits = preg_replace('/\D+/', '', $keyword) ?: $keyword;
                $query->where(function ($builder) use ($keyword, $digits): void {
                    $builder->where('nop', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('jalan_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('jalan_op_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('desa_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhereRaw('REPLACE(REPLACE(REPLACE(REPLACE(nop, ".", ""), "-", ""), " ", ""), "/", "") LIKE ?', ['%' . $digits . '%']);
                });
            })
            ->when($year, fn ($query) => $query->where('tax_year', $year))
            ->orderByDesc('tax_year')
            ->orderBy('nop')
            ->paginate(25)
            ->withQueryString();

        $availableYears = PbbTaxObject::query()
            ->select('tax_year')
            ->whereNotNull('tax_year')
            ->distinct()
            ->orderByDesc('tax_year')
            ->pluck('tax_year');

        return view('dashboard.pbb-tax-objects.index', [
            'taxObjects' => $taxObjects,
            'availableYears' => $availableYears,
            'filters' => [
                'q' => $keyword,
                'year' => $year,
            ],
        ]);
    }

    public function create()
    {
        return view('dashboard.pbb-tax-objects.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $payload = $this->normalizePayload($validated);

        PbbTaxObject::query()->create($payload);

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        return view('dashboard.pbb-tax-objects.show', compact('taxObject'));
    }

    public function edit(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        return view('dashboard.pbb-tax-objects.edit', compact('taxObject'));
    }

    public function update(Request $request, string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        $validated = $this->validatePayload($request, $taxObject->id);
        $payload = $this->normalizePayload($validated);

        $taxObject->update($payload);

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        $taxObject->delete();

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $payload = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
            'year_override' => ['nullable', 'integer', 'min:2026', 'max:' . (date('Y') + 1)],
        ]);

        $file = $request->file('file');
        if ($file === null) {
            return redirect()->back()->withErrors(['file' => 'File import tidak ditemukan.']);
        }

        $csvDelimiter = SpreadsheetImportHelper::detectCsvDelimiter($file);
        $readerType = SpreadsheetImportHelper::resolveReaderType($file);

        $import = new PbbTaxObjectsImport(
            isset($payload['year_override']) ? (int) $payload['year_override'] : null,
            $file->getClientOriginalName(),
            $csvDelimiter,
        );

        try {
            Excel::import($import, $file, null, $readerType);
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->back()->withErrors([
                'file' => 'Import gagal diproses. Pastikan format kolom sesuai template dan file tidak rusak.',
            ]);
        }

        $summary = $import->summary();

        $message = "Import selesai: {$summary['inserted']} data baru, {$summary['updated']} data diperbarui, {$summary['skipped']} baris dilewati.";

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', $message);
    }

    public function destroyByYear(Request $request)
    {
        $payload = $request->validate([
            'year' => ['required', 'integer', 'min:2025', 'max:' . (date('Y') + 20)],
        ]);

        $year = (int) $payload['year'];
        $deleted = PbbTaxObject::query()->where('tax_year', $year)->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('dashboard.pbb-tax-objects.index')
                ->with('success', "Tidak ada data PBB tahun {$year} yang dihapus.");
        }

        return redirect()
            ->route('dashboard.pbb-tax-objects.index')
            ->with('success', "Berhasil menghapus {$deleted} data PBB tahun {$year}.");
    }

    public function template()
    {
        $columns = [
            'nop',
            'tax_year',
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
        ];

        return response()->streamDownload(function () use ($columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            fclose($handle);
        }, 'template-master-pbb.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nop' => [
                'required',
                'string',
                'max:40',
                Rule::unique('pbb_tax_objects', 'nop')
                    ->where(fn ($query) => $query->where('tax_year', (int) $request->input('tax_year')))
                    ->ignore($id),
            ],
            'tax_year' => ['required', 'integer', 'min:2026', 'max:' . (date('Y') + 1)],
            'nama_wp_sppt' => ['required', 'string', 'max:255'],
            'jalan_wp_sppt' => ['required', 'string', 'max:255'],
            'rt_wp_sppt' => ['nullable', 'digits_between:1,3'],
            'rw_wp_sppt' => ['nullable', 'digits_between:1,3'],
            'desa_wp_sppt' => ['required', 'string', 'max:150'],
            'jalan_op_sppt' => ['required', 'string', 'max:255'],
            'rt_op_sppt' => ['nullable', 'digits_between:1,3'],
            'rw_op_sppt' => ['nullable', 'digits_between:1,3'],
            'luas_tanah_sppt' => ['nullable', 'numeric', 'min:0'],
            'luas_bangunan_sppt' => ['nullable', 'numeric', 'min:0'],
            'pbb_terhutang' => ['required', 'numeric', 'min:0'],
            'tanggal_pembayaran' => ['nullable', 'date'],
        ]);
    }

    private function normalizePayload(array $validated): array
    {
        $validated['rt_wp_sppt'] = $this->normalizeCode($validated['rt_wp_sppt'] ?? null);
        $validated['rw_wp_sppt'] = $this->normalizeCode($validated['rw_wp_sppt'] ?? null);
        $validated['rt_op_sppt'] = $this->normalizeCode($validated['rt_op_sppt'] ?? null);
        $validated['rw_op_sppt'] = $this->normalizeCode($validated['rw_op_sppt'] ?? null);

        return [
            'nop' => trim((string) $validated['nop']),
            'tax_year' => (int) $validated['tax_year'],
            'nama_wp_sppt' => trim((string) $validated['nama_wp_sppt']),
            'jalan_wp_sppt' => trim((string) $validated['jalan_wp_sppt']),
            'rt_wp_sppt' => $validated['rt_wp_sppt'],
            'rw_wp_sppt' => $validated['rw_wp_sppt'],
            'desa_wp_sppt' => trim((string) $validated['desa_wp_sppt']),
            'jalan_op_sppt' => trim((string) $validated['jalan_op_sppt']),
            'rt_op_sppt' => $validated['rt_op_sppt'],
            'rw_op_sppt' => $validated['rw_op_sppt'],
            'luas_tanah_sppt' => $validated['luas_tanah_sppt'] ?? 0,
            'luas_bangunan_sppt' => $validated['luas_bangunan_sppt'] ?? 0,
            'pbb_terhutang' => $validated['pbb_terhutang'],
            'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? null,
            'tax_name' => trim((string) $validated['nama_wp_sppt']),
            'owner_name' => trim((string) $validated['nama_wp_sppt']),
            'location' => trim((string) $validated['jalan_op_sppt']),
            'tax_address' => trim((string) $validated['jalan_wp_sppt']),
            'land_area' => $validated['luas_tanah_sppt'] ?? 0,
            'building_area' => $validated['luas_bangunan_sppt'] ?? 0,
            'amount_due' => $validated['pbb_terhutang'],
            'status' => ! empty($validated['tanggal_pembayaran']) ? 'Lunas' : 'Belum Lunas',
        ];
    }

    private function normalizeCode(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if ($digits === '') {
            return null;
        }

        return str_pad($digits, 3, '0', STR_PAD_LEFT);
    }
}
