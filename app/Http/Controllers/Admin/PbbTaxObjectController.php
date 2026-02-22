<?php

namespace App\Http\Controllers\Admin;

use App\Models\PbbTaxObject;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PbbTaxObjectController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        $taxObjects = PbbTaxObject::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('nop', 'like', '%' . $keyword . '%')
                        ->orWhere('tax_name', 'like', '%' . $keyword . '%')
                        ->orWhere('owner_name', 'like', '%' . $keyword . '%')
                        ->orWhere('location', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('tax_year')
            ->orderBy('nop')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.pbb-tax-objects.index', [
            'taxObjects' => $taxObjects,
            'filters' => [
                'q' => $keyword,
            ],
        ]);
    }

    public function create()
    {
        return view('dashboard.pbb-tax-objects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nop' => 'required|string|unique:pbb_tax_objects|max:20',
            'tax_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'land_area' => 'required|numeric',
            'building_area' => 'required|numeric',
            'tax_year' => 'required|integer',
            'amount_due' => 'required|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        PbbTaxObject::create($validated);
        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil ditambahkan');
    }

    public function show($id)
    {
        $taxObject = PbbTaxObject::findOrFail($id);
        return view('dashboard.pbb-tax-objects.show', compact('taxObject'));
    }

    public function edit($id)
    {
        $taxObject = PbbTaxObject::findOrFail($id);
        return view('dashboard.pbb-tax-objects.edit', compact('taxObject'));
    }

    public function update(Request $request, $id)
    {
        $taxObject = PbbTaxObject::findOrFail($id);
        
        $validated = $request->validate([
            'nop' => 'required|string|unique:pbb_tax_objects,nop,' . $id . '|max:20',
            'tax_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'land_area' => 'required|numeric',
            'building_area' => 'required|numeric',
            'tax_year' => 'required|integer',
            'amount_due' => 'required|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $taxObject->update($validated);
        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil diperbarui');
    }

    public function destroy($id)
    {
        $taxObject = PbbTaxObject::findOrFail($id);
        $taxObject->delete();
        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil dihapus');
    }

    public function import(Request $request)
    {
        // Handle Excel import here
        return back()->with('success', 'Data impor sedang diproses');
    }
}
