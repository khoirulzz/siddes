@extends('layouts.dashboard')

@section('title', 'Edit Data Kartu Keluarga')
@section('page_title', 'Edit Data Kartu Keluarga')

@section('content')
    <section class="panel household-form-hero">
        <a class="back-link" href="{{ route('dashboard.population-households.show', $household) }}">← Kembali ke detail KK</a>
        <span class="eyebrow">Data bersama seluruh anggota</span>
        <h2>Edit KK <span class="identifier">{{ $household->no_kk }}</span></h2>
        <p class="muted">Perubahan alamat dan wilayah di sini akan diterapkan ke seluruh anggota aktif pada KK ini.</p>
    </section>

    <section class="panel">
        <form method="POST" action="{{ route('dashboard.population-households.update', $household) }}">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="field">
                    <label>Nomor KK</label>
                    <input type="text" value="{{ $household->no_kk }}" readonly>
                    <small class="muted">Nomor KK tidak diubah dari halaman ini.</small>
                </div>
                <div class="field">
                    <label for="nama_kepala_keluarga">Nama Kepala Keluarga</label>
                    <input id="nama_kepala_keluarga" type="text" name="nama_kepala_keluarga" value="{{ old('nama_kepala_keluarga', $household->nama_kepala_keluarga) }}">
                </div>
                <div class="field full">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat">{{ old('alamat', $household->alamat) }}</textarea>
                </div>
                <div class="field"><label for="rt">RT</label><input id="rt" type="text" inputmode="numeric" name="rt" value="{{ old('rt', $household->rt) }}"></div>
                <div class="field"><label for="rw">RW</label><input id="rw" type="text" inputmode="numeric" name="rw" value="{{ old('rw', $household->rw) }}"></div>
                <div class="field"><label for="kode_pos">Kode Pos</label><input id="kode_pos" type="text" inputmode="numeric" name="kode_pos" value="{{ old('kode_pos', $household->kode_pos) }}"></div>
                <div class="field">
                    <label for="dusun">Dusun</label>
                    <select id="dusun" name="dusun" required>
                        @foreach($hamlets as $hamlet)
                            <option value="{{ $hamlet }}" @selected(old('dusun', $household->dusun) === $hamlet)>{{ $hamlet }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="desa">Desa/Kelurahan</label><input id="desa" type="text" name="desa" value="{{ old('desa', $household->desa) }}"></div>
                <div class="field"><label for="kecamatan">Kecamatan</label><input id="kecamatan" type="text" name="kecamatan" value="{{ old('kecamatan', $household->kecamatan) }}"></div>
                <div class="field"><label for="kabupaten">Kabupaten/Kota</label><input id="kabupaten" type="text" name="kabupaten" value="{{ old('kabupaten', $household->kabupaten) }}"></div>
                <div class="field"><label for="provinsi">Provinsi</label><input id="provinsi" type="text" name="provinsi" value="{{ old('provinsi', $household->provinsi) }}"></div>
            </div>
            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Simpan Perubahan KK</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-households.show', $household) }}">Batal</a>
            </div>
        </form>
    </section>
@endsection
