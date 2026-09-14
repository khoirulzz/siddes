<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\PopulationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseholdController extends Controller
{
    public function show(Household $household)
    {
        $household->load([
            'currentMembers' => fn ($query) => $query
                ->with('resident')
                ->orderByRaw('CASE WHEN no_urut_kk IS NULL THEN 1 ELSE 0 END')
                ->orderBy('no_urut_kk')
                ->orderBy('id'),
        ]);

        return view('dashboard.population.household-show', compact('household'));
    }

    public function edit(Household $household)
    {
        return view('dashboard.population.household-form', [
            'household' => $household,
            'hamlets' => PopulationRecord::HAMLETS,
        ]);
    }

    public function update(Request $request, Household $household)
    {
        $validated = $request->validate([
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
        ]);

        $validated['rt'] = filled($validated['rt'] ?? null) ? str_pad($validated['rt'], 3, '0', STR_PAD_LEFT) : null;
        $validated['rw'] = filled($validated['rw'] ?? null) ? str_pad($validated['rw'], 3, '0', STR_PAD_LEFT) : null;
        $validated['kode_pos'] = $validated['kode_pos'] ?: PopulationRecord::DEFAULT_POSTAL_CODE;
        $validated['desa'] = $validated['desa'] ?: PopulationRecord::DEFAULT_VILLAGE;
        $validated['kecamatan'] = $validated['kecamatan'] ?: PopulationRecord::DEFAULT_DISTRICT;
        $validated['kabupaten'] = $validated['kabupaten'] ?: PopulationRecord::DEFAULT_REGENCY;
        $validated['provinsi'] = $validated['provinsi'] ?: PopulationRecord::DEFAULT_PROVINCE;

        DB::transaction(function () use ($household, $validated): void {
            $household->update($validated);

            $residentIds = $household->currentMembers()->pluck('resident_id');
            PopulationRecord::query()->whereIn('id', $residentIds)->update([
                'no_kk' => $household->no_kk,
                'nkk' => $household->no_kk,
                'rt' => $validated['rt'],
                'rw' => $validated['rw'],
                'dusun' => $validated['dusun'],
                'hamlet' => $validated['dusun'],
                'desa' => $validated['desa'],
                'kecamatan' => $validated['kecamatan'],
                'kabupaten' => $validated['kabupaten'],
                'provinsi' => $validated['provinsi'],
                'kode_pos' => $validated['kode_pos'],
                'address_detail' => $validated['alamat'],
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('dashboard.population-households.show', $household)
            ->with('success', 'Data kartu keluarga berhasil diperbarui untuk seluruh anggota aktif.');
    }
}
