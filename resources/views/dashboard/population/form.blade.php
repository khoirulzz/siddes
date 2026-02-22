@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    <section class="panel">
        <form method="POST" action="{{ $route }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="form-grid">
                <div class="field">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap ?: $item->full_name) }}" required>
                </div>

                <div class="field">
                    <label for="nik">NIK</label>
                    <input id="nik" type="text" name="nik" inputmode="numeric" value="{{ old('nik', $item->nik) }}" required>
                </div>

                <div class="field">
                    <label for="no_kk">No. KK</label>
                    <input id="no_kk" type="text" name="no_kk" inputmode="numeric" value="{{ old('no_kk', $item->no_kk ?: $item->nkk) }}" required>
                </div>

                <div class="field">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="Laki-laki" @selected(old('jenis_kelamin', $item->jenis_kelamin ?: $item->gender) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('jenis_kelamin', $item->jenis_kelamin ?: $item->gender) === 'Perempuan')>Perempuan</option>
                    </select>
                </div>

                <div class="field">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input id="tempat_lahir" type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $item->tempat_lahir ?: $item->birth_place) }}" required>
                </div>

                <div class="field">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $item->tanggal_lahir?->format('Y-m-d') ?: $item->birth_date?->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="agama">Agama</label>
                    <input id="agama" type="text" name="agama" value="{{ old('agama', $item->agama ?: $item->religion) }}" required>
                </div>

                <div class="field">
                    <label for="pendidikan">Pendidikan</label>
                    <input id="pendidikan" type="text" name="pendidikan" value="{{ old('pendidikan', $item->pendidikan) }}">
                </div>

                <div class="field">
                    <label for="pekerjaan">Pekerjaan</label>
                    <input id="pekerjaan" type="text" name="pekerjaan" value="{{ old('pekerjaan', $item->pekerjaan ?: $item->occupation) }}" required>
                </div>

                <div class="field">
                    <label for="status_perkawinan">Status Perkawinan</label>
                    <input id="status_perkawinan" type="text" name="status_perkawinan" value="{{ old('status_perkawinan', $item->status_perkawinan) }}">
                </div>

                <div class="field">
                    <label for="kewarganegaraan">Kewarganegaraan</label>
                    <input id="kewarganegaraan" type="text" name="kewarganegaraan" value="{{ old('kewarganegaraan', $item->kewarganegaraan ?: 'WNI') }}">
                </div>

                <div class="field">
                    <label for="rt">RT</label>
                    <input id="rt" type="text" name="rt" inputmode="numeric" value="{{ old('rt', $item->rt) }}">
                </div>

                <div class="field">
                    <label for="rw">RW</label>
                    <input id="rw" type="text" name="rw" inputmode="numeric" value="{{ old('rw', $item->rw) }}">
                </div>

                <div class="field">
                    <label for="dusun">Dusun</label>
                    <select id="dusun" name="dusun" required>
                        <option value="">Pilih Dusun</option>
                        @foreach($hamlets as $hamlet)
                            <option value="{{ $hamlet }}" @selected(old('dusun', $item->dusun ?: $item->hamlet) === $hamlet)>
                                {{ $hamlet }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="desa">Desa</label>
                    <input id="desa" type="text" name="desa" value="{{ old('desa', $item->desa ?: \App\Models\PopulationRecord::DEFAULT_VILLAGE) }}">
                </div>

                <div class="field">
                    <label for="kecamatan">Kecamatan</label>
                    <input id="kecamatan" type="text" name="kecamatan" value="{{ old('kecamatan', $item->kecamatan ?: \App\Models\PopulationRecord::DEFAULT_DISTRICT) }}">
                </div>

                <div class="field">
                    <label for="kabupaten">Kabupaten</label>
                    <input id="kabupaten" type="text" name="kabupaten" value="{{ old('kabupaten', $item->kabupaten ?: \App\Models\PopulationRecord::DEFAULT_REGENCY) }}">
                </div>

                <div class="field">
                    <label for="provinsi">Provinsi</label>
                    <input id="provinsi" type="text" name="provinsi" value="{{ old('provinsi', $item->provinsi ?: \App\Models\PopulationRecord::DEFAULT_PROVINCE) }}">
                </div>

                <div class="field">
                    <label for="kode_pos">Kode Pos</label>
                    <input id="kode_pos" type="text" name="kode_pos" inputmode="numeric" value="{{ old('kode_pos', $item->kode_pos ?: \App\Models\PopulationRecord::DEFAULT_POSTAL_CODE) }}">
                </div>

                <div class="field full">
                    <label for="address_detail">Alamat Lengkap</label>
                    <textarea id="address_detail" name="address_detail">{{ old('address_detail', $item->address_detail) }}</textarea>
                    <small class="muted">Kolom desa-kecamatan-kabupaten-provinsi sudah otomatis terisi dan tetap bisa disesuaikan bila diperlukan.</small>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-records.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
