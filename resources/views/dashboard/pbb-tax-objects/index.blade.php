@extends('layouts.dashboard')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 text-dark fw-bold">Master Data PBB</h1>
            <p class="text-muted">Kelola data pajak bumi dan bangunan</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('dashboard.pbb-tax-objects.create') }}" class="btn" style="background-color: #0066cc; color: white; border: none;">
                <i class="bi bi-plus-circle"></i> Tambah Data PBB
            </a>
        </div>
    </div>

    @if($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.pbb-tax-objects.index') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="q" class="form-label mb-1">Cari NOP / Nama / Pemilik / Lokasi</label>
                    <input id="q" type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: 33.26.010.004.001-0028.0">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="{{ route('dashboard.pbb-tax-objects.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                    <tr>
                        <th style="color: #0066cc; font-weight: 600;">NOP</th>
                        <th style="color: #0066cc; font-weight: 600;">Nama Pajak</th>
                        <th style="color: #0066cc; font-weight: 600;">Pemilik</th>
                        <th style="color: #0066cc; font-weight: 600;">Lokasi</th>
                        <th style="color: #0066cc; font-weight: 600;">Th. Pajak</th>
                        <th style="color: #0066cc; font-weight: 600;">Jumlah</th>
                        <th style="color: #0066cc; font-weight: 600;">Status</th>
                        <th style="color: #0066cc; font-weight: 600; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxObjects as $taxObject)
                        <tr>
                            <td class="fw-bold text-dark">{{ $taxObject->nop }}</td>
                            <td>{{ $taxObject->tax_name }}</td>
                            <td>{{ $taxObject->owner_name }}</td>
                            <td>{{ Str::limit($taxObject->location, 30) }}</td>
                            <td class="text-center">{{ $taxObject->tax_year }}</td>
                            <td class="text-end">Rp {{ number_format($taxObject->amount_due, 0, ',', '.') }}</td>
                            <td>
                                @if($taxObject->status === 'active')
                                    <span class="badge" style="background-color: #00a8e8; color: white;">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('dashboard.pbb-tax-objects.show', $taxObject->id) }}" class="btn" style="background-color: #00a8e8; color: white; border: none;" title="Lihat">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('dashboard.pbb-tax-objects.edit', $taxObject->id) }}" class="btn" style="background-color: #0066cc; color: white; border: none;" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('dashboard.pbb-tax-objects.destroy', $taxObject->id) }}" style="display: inline;" onsubmit="return confirm('Yakin hapus data ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox"></i> Belum ada data PBB
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($taxObjects->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Menampilkan {{ $taxObjects->from() ?? 0 }} - {{ $taxObjects->to() ?? 0 }} dari {{ $taxObjects->total() }} data</small>
            <div class="pager-controls">
                @if($taxObjects->onFirstPage())
                    <span class="pager-link is-disabled">Sebelumnya</span>
                @else
                    <a class="pager-link" href="{{ $taxObjects->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="pager-meta">Halaman {{ $taxObjects->currentPage() }} / {{ $taxObjects->lastPage() }}</span>

                @if($taxObjects->hasMorePages())
                    <a class="pager-link" href="{{ $taxObjects->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="pager-link is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
