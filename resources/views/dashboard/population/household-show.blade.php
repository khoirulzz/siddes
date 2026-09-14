@extends('layouts.dashboard')

@section('title', 'Detail Kartu Keluarga')
@section('page_title', 'Detail Kartu Keluarga')

@section('content')
    @php
        $members = $household->currentMembers->filter(fn ($member) => $member->resident);
        $maleTotal = $members->filter(fn ($member) => $member->resident->resolvedGender() === 'Laki-laki')->count();
        $femaleTotal = $members->filter(fn ($member) => $member->resident->resolvedGender() === 'Perempuan')->count();
    @endphp

    <section class="panel household-detail-hero">
        <div class="household-detail-hero__top">
            <div>
                <a class="back-link" href="{{ route('dashboard.population-records.index', ['view' => 'kk']) }}">← Kembali ke daftar KK</a>
                <span class="eyebrow">Nomor Kartu Keluarga</span>
                <h2 class="identifier">{{ $household->no_kk }}</h2>
                <p>{{ $household->nama_kepala_keluarga ?: 'Kepala keluarga belum ditetapkan' }}</p>
            </div>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('dashboard.population-records.create', ['household' => $household->id]) }}">Tambah Anggota</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-households.edit', $household) }}">Edit Data KK</a>
            </div>
        </div>

        <div class="household-detail-grid">
            <div><span>Alamat</span><strong>{{ $household->alamat ?: '-' }}</strong></div>
            <div><span>Dusun</span><strong>{{ $household->dusun ?: '-' }}</strong></div>
            <div><span>RT / RW</span><strong>{{ $household->rt ?: '-' }} / {{ $household->rw ?: '-' }}</strong></div>
            <div><span>Desa</span><strong>{{ $household->desa ?: '-' }}</strong></div>
            <div><span>Kecamatan</span><strong>{{ $household->kecamatan ?: '-' }}</strong></div>
            <div><span>Kabupaten / Provinsi</span><strong>{{ $household->kabupaten ?: '-' }} / {{ $household->provinsi ?: '-' }}</strong></div>
        </div>
    </section>

    <section class="household-member-stats">
        <article><span>Total anggota</span><strong>{{ $members->count() }}</strong></article>
        <article><span>Laki-laki</span><strong>{{ $maleTotal }}</strong></article>
        <article><span>Perempuan</span><strong>{{ $femaleTotal }}</strong></article>
    </section>

    <section class="panel population-list-panel">
        <div class="toolbar">
            <div><h2>Anggota Keluarga</h2><p class="muted">Diurutkan sesuai nomor urut pada KK.</p></div>
            <span class="result-count">{{ $members->count() }} orang</span>
        </div>

        <div class="table-wrap population-table-wrap population-table-wrap--compact">
            <table class="responsive-data-table">
                <thead><tr><th>No.</th><th>Nama / NIK</th><th>Hubungan</th><th>Jenis Kelamin</th><th>Tempat, Tanggal Lahir</th><th>Pekerjaan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($members as $member)
                        @php($resident = $member->resident)
                        <tr>
                            <td data-label="No."><strong>{{ $member->no_urut_kk ?: '-' }}</strong></td>
                            <td data-label="Nama / NIK"><strong>{{ $resident->resolvedName() }}</strong><small class="table-subtext identifier">{{ $resident->nik }}</small></td>
                            <td data-label="Hubungan"><span class="status-pill {{ $member->is_kepala_keluarga ? 'status-pill--success' : '' }}">{{ $member->status_hubungan ?: '-' }}</span></td>
                            <td data-label="Jenis Kelamin">{{ $resident->resolvedGender() }}</td>
                            <td data-label="TTL">{{ $resident->resolvedBirthPlace() }}<small class="table-subtext">{{ $resident->resolvedBirthDate()?->format('d-m-Y') ?: '-' }}</small></td>
                            <td data-label="Pekerjaan">{{ $resident->resolvedOccupation() }}</td>
                            <td data-label="Aksi">
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.population-records.edit', ['population_record' => $resident, 'household' => $household->id]) }}">Edit</a>
                                    <form action="{{ route('dashboard.population-records.destroy', $resident) }}" method="POST" onsubmit="return confirm('Hapus penduduk ini beserta seluruh riwayat keanggotaan KK?')">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="context_household_id" value="{{ $household->id }}">
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><strong>KK ini belum memiliki anggota aktif</strong><span>Tambahkan anggota pertama untuk melengkapi data keluarga.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
