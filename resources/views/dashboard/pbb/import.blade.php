@extends('layouts.dashboard')

@section('title', 'Import Data PBB dari Excel')

@section('content')
<div class="container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Import Data PBB dari Excel</h2>
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
                <div class="card-header">
                    <h5 class="card-title">Format File Excel</h5>
                </div>
                <div class="card-body">
                    <p>File Excel harus memiliki kolom berikut sesuai urutan:</p>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th width="80">Kolom</th>
                                <th>Nama</th>
                                <th>Tipe Data</th>
                                <th>Contoh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>A</td>
                                <td>NOP</td>
                                <td>Text</td>
                                <td>33.26.010.001.001</td>
                            </tr>
                            <tr>
                                <td>B</td>
                                <td>Nama Wajib Pajak</td>
                                <td>Text</td>
                                <td>Budi Santoso</td>
                            </tr>
                            <tr>
                                <td>C</td>
                                <td>Alamat</td>
                                <td>Text</td>
                                <td>Jl. Merdeka No. 10</td>
                            </tr>
                            <tr>
                                <td>D</td>
                                <td>Tahun</td>
                                <td>Angka</td>
                                <td>2026</td>
                            </tr>
                            <tr>
                                <td>E</td>
                                <td>Jumlah Tagihan</td>
                                <td>Angka</td>
                                <td>1500000</td>
                            </tr>
                            <tr>
                                <td>F</td>
                                <td>Status</td>
                                <td>Text</td>
                                <td>Belum Lunas / Lunas</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">Upload File</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.pbb-tax-objects.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Pilih File Excel</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv" required>
                            <small class="form-text text-muted">Format: .xlsx, .xls, atau .csv</small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Import
                            </button>
                            <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success"></i> Pastikan file tidak kosong</li>
                        <li><i class="bi bi-check-circle text-success"></i> Kolom header harus ada di baris pertama</li>
                        <li><i class="bi bi-check-circle text-success"></i> NOP harus unik</li>
                        <li><i class="bi bi-check-circle text-success"></i> Data yang ada akan di-update otomatis</li>
                        <li><i class="bi bi-check-circle text-success"></i> Tahun minimal 2000</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
