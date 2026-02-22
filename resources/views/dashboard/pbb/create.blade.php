@extends('layouts.dashboard')

@section('title', 'Tambah Data PBB')

@section('content')
<div class="container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Tambah Data PBB</h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('dashboard.pbb-tax-objects.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nomor Objek Pajak (NOP)</label>
                            <input type="text" name="nop" class="form-control @error('nop') is-invalid @enderror" 
                                   value="{{ old('nop') }}" placeholder="Contoh: 33.26.010.001.001" required>
                            @error('nop')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Wajib Pajak</label>
                            <input type="text" name="tax_name" class="form-control @error('tax_name') is-invalid @enderror"
                                   value="{{ old('tax_name') }}" required>
                            @error('tax_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Objek Pajak</label>
                            <textarea name="tax_address" class="form-control @error('tax_address') is-invalid @enderror" 
                                      rows="3" required>{{ old('tax_address') }}</textarea>
                            @error('tax_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Pajak</label>
                                <input type="number" name="tax_year" class="form-control @error('tax_year') is-invalid @enderror"
                                       value="{{ old('tax_year', date('Y')) }}" min="2000" required>
                                @error('tax_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jumlah Tagihan</label>
                                <input type="number" name="amount_due" class="form-control @error('amount_due') is-invalid @enderror"
                                       value="{{ old('amount_due') }}" step="0.01" min="0" required>
                                @error('amount_due')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="">Pilih Status</option>
                                <option value="Belum Lunas" {{ old('status') == 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                <option value="Lunas" {{ old('status') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                            <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
