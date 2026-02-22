@extends('layouts.dashboard')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 text-dark fw-bold">Detail Data PBB</h1>
            <p class="text-muted">NOP: <strong>{{ $taxObject->nop }}</strong></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('dashboard.pbb-tax-objects.edit', $taxObject->id) }}" class="btn" style="background-color: #0066cc; color: white; border: none;">
                <i class="bi bi-pencil"></i> Edit Data
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                    <h5 class="mb-0" style="color: #0066cc; font-weight: 600;">Informasi Pajak</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Nomor Objek Pajak (NOP)</p>
                            <p class="fw-bold fs-5">{{ $taxObject->nop }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Tahun Pajak</p>
                            <p class="fw-bold fs-5">{{ $taxObject->tax_year }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Nama Pajak</p>
                            <p class="fw-bold">{{ $taxObject->tax_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Status</p>
                            @if($taxObject->status === 'active')
                                <span class="badge" style="background-color: #00a8e8; color: white; font-size: 0.95rem; padding: 0.5rem 0.75rem;">Aktif</span>
                            @else
                                <span class="badge bg-secondary" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                    <h5 class="mb-0" style="color: #0066cc; font-weight: 600;">Informasi Pemilik & Lokasi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted mb-1">Nama Pemilik</p>
                        <p class="fw-bold">{{ $taxObject->owner_name }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1">Lokasi Properti</p>
                        <p class="fw-bold">{{ $taxObject->location }}</p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                    <h5 class="mb-0" style="color: #0066cc; font-weight: 600;">Luas & Nilai Pajak</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Luas Tanah</p>
                            <p class="fw-bold fs-5">{{ number_format($taxObject->land_area, 2, ',', '.') }} m²</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Luas Bangunan</p>
                            <p class="fw-bold fs-5">{{ number_format($taxObject->building_area, 2, ',', '.') }} m²</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1">Jumlah Pajak Terhutang</p>
                        <p class="fw-bold fs-5" style="color: #0066cc;">Rp {{ number_format($taxObject->amount_due, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                    <h5 class="mb-0" style="color: #0066cc; font-weight: 600;">Aksi</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('dashboard.pbb-tax-objects.edit', $taxObject->id) }}" class="btn btn-sm btn-block w-100 mb-2" style="background-color: #0066cc; color: white; border: none;">
                        <i class="bi bi-pencil"></i> Edit Data
                    </a>
                    <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-sm btn-block w-100 mb-2 btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <form action="{{ route('dashboard.pbb-tax-objects.destroy', $taxObject->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-block w-100 btn-danger">
                            <i class="bi bi-trash"></i> Hapus Data
                        </button>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <small>
                    <i class="bi bi-info-circle"></i> 
                    Data ini dapat diperbarui kapan saja. Pastikan informasi sudah akurat sebelum menyimpan.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
