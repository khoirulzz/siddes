@extends('layouts.dashboard')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 text-dark fw-bold">Edit Data PBB</h1>
            <p class="text-muted">Perbarui informasi data PBB</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('dashboard.pbb-tax-objects.update', $taxObject->id) }}" method="POST" novalidate class="needs-validation">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nop" class="form-label fw-bold" style="color: #0066cc;">NOP (Nomor Objek Pajak)</label>
                        <input type="text" class="form-control @error('nop') is-invalid @enderror" id="nop" name="nop" value="{{ old('nop', $taxObject->nop) }}" required maxlength="20" placeholder="Contoh: 31.30.000.000.0001">
                        @error('nop')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="tax_year" class="form-label fw-bold" style="color: #0066cc;">Tahun Pajak</label>
                        <input type="number" class="form-control @error('tax_year') is-invalid @enderror" id="tax_year" name="tax_year" value="{{ old('tax_year', $taxObject->tax_year) }}" required min="1900" max="2100">
                        @error('tax_year')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="tax_name" class="form-label fw-bold" style="color: #0066cc;">Nama Pajak</label>
                    <input type="text" class="form-control @error('tax_name') is-invalid @enderror" id="tax_name" name="tax_name" value="{{ old('tax_name', $taxObject->tax_name) }}" required maxlength="255" placeholder="Contoh: Tanah dan Bangunan Residensial">
                    @error('tax_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="owner_name" class="form-label fw-bold" style="color: #0066cc;">Nama Pemilik</label>
                    <input type="text" class="form-control @error('owner_name') is-invalid @enderror" id="owner_name" name="owner_name" value="{{ old('owner_name', $taxObject->owner_name) }}" required maxlength="255" placeholder="Nama lengkap pemilik">
                    @error('owner_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label fw-bold" style="color: #0066cc;">Lokasi</label>
                    <textarea class="form-control @error('location') is-invalid @enderror" id="location" name="location" rows="2" required placeholder="Alamat lengkap lokasi properti">{{ old('location', $taxObject->location) }}</textarea>
                    @error('location')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="land_area" class="form-label fw-bold" style="color: #0066cc;">Luas Tanah (m²)</label>
                        <input type="number" class="form-control @error('land_area') is-invalid @enderror" id="land_area" name="land_area" value="{{ old('land_area', $taxObject->land_area) }}" required step="0.01" min="0" placeholder="0">
                        @error('land_area')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="building_area" class="form-label fw-bold" style="color: #0066cc;">Luas Bangunan (m²)</label>
                        <input type="number" class="form-control @error('building_area') is-invalid @enderror" id="building_area" name="building_area" value="{{ old('building_area', $taxObject->building_area) }}" required step="0.01" min="0" placeholder="0">
                        @error('building_area')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="amount_due" class="form-label fw-bold" style="color: #0066cc;">Jumlah Pajak Terhutang (Rp)</label>
                        <input type="number" class="form-control @error('amount_due') is-invalid @enderror" id="amount_due" name="amount_due" value="{{ old('amount_due', $taxObject->amount_due) }}" required step="0.01" min="0" placeholder="0">
                        @error('amount_due')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label fw-bold" style="color: #0066cc;">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="active" {{ old('status', $taxObject->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status', $taxObject->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn flex-grow-1" style="background-color: #0066cc; color: white; border: none;">
                        <i class="bi bi-check-circle"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
