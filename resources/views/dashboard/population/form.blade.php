@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    @php
        $household = $item->currentMembership?->household;
    @endphp

    <section class="panel">
        <form method="POST" action="{{ $route }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <h2 style="margin-bottom:0.85rem;">Data Wilayah & Keluarga</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="no_kk">Nomor KK</label>
                    <input id="no_kk" type="text" name="no_kk" inputmode="numeric" value="{{ old('no_kk', $item->no_kk ?: $item->nkk ?: $household?->no_kk) }}" required>
                </div>

                <div class="field">
                    <label for="nama_kepala_keluarga">Nama Kepala Keluarga</label>
                    <input id="nama_kepala_keluarga" type="text" name="nama_kepala_keluarga" value="{{ old('nama_kepala_keluarga', $item->nama_kepala_keluarga ?: $household?->nama_kepala_keluarga) }}">
                </div>

                <div class="field full">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" placeholder="Contoh: RT 001 RW 002 Dusun Bojongireng">{{ old('alamat', $item->address_detail ?: $household?->alamat) }}</textarea>
                </div>

                <div class="field">
                    <label for="rt">RT</label>
                    <input id="rt" type="text" name="rt" inputmode="numeric" value="{{ old('rt', $item->rt ?: $household?->rt) }}">
                </div>

                <div class="field">
                    <label for="rw">RW</label>
                    <input id="rw" type="text" name="rw" inputmode="numeric" value="{{ old('rw', $item->rw ?: $household?->rw) }}">
                </div>

                <div class="field">
                    <label for="kode_pos">Kode Pos</label>
                    <input id="kode_pos" type="text" name="kode_pos" inputmode="numeric" value="{{ old('kode_pos', $item->kode_pos ?: $household?->kode_pos ?: \App\Models\PopulationRecord::DEFAULT_POSTAL_CODE) }}">
                </div>

                <div class="field">
                    <label for="dusun">Dusun</label>
                    <select id="dusun" name="dusun" required>
                        <option value="">Pilih Dusun</option>
                        @foreach($hamlets as $hamlet)
                            <option value="{{ $hamlet }}" @selected(old('dusun', $item->dusun ?: $item->hamlet ?: $household?->dusun) === $hamlet)>
                                {{ $hamlet }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="desa">Desa/Kelurahan</label>
                    <input id="desa" type="text" name="desa" value="{{ old('desa', $item->desa ?: $household?->desa ?: \App\Models\PopulationRecord::DEFAULT_VILLAGE) }}">
                </div>

                <div class="field">
                    <label for="kecamatan">Kecamatan</label>
                    <input id="kecamatan" type="text" name="kecamatan" value="{{ old('kecamatan', $item->kecamatan ?: $household?->kecamatan ?: \App\Models\PopulationRecord::DEFAULT_DISTRICT) }}">
                </div>

                <div class="field">
                    <label for="kabupaten">Kabupaten/Kota</label>
                    <input id="kabupaten" type="text" name="kabupaten" value="{{ old('kabupaten', $item->kabupaten ?: $household?->kabupaten ?: \App\Models\PopulationRecord::DEFAULT_REGENCY) }}">
                </div>

                <div class="field">
                    <label for="provinsi">Provinsi</label>
                    <input id="provinsi" type="text" name="provinsi" value="{{ old('provinsi', $item->provinsi ?: $household?->provinsi ?: \App\Models\PopulationRecord::DEFAULT_PROVINCE) }}">
                </div>
            </div>

            <h2 style="margin:1.1rem 0 0.85rem;">Biodata Anggota Keluarga</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="no_urut_kk">No Urut di KK</label>
                    <input id="no_urut_kk" type="number" min="1" max="999" name="no_urut_kk" value="{{ old('no_urut_kk', $item->currentMembership?->no_urut_kk) }}">
                </div>

                <div class="field">
                    <label for="status_hubungan">Status Hubungan Dalam Keluarga</label>
                    <select id="status_hubungan" name="status_hubungan" required>
                        @foreach($statusHubunganOptions as $statusHubungan)
                            <option value="{{ $statusHubungan }}" @selected(old('status_hubungan', $item->status_hubungan ?: $item->currentMembership?->status_hubungan ?: 'Kepala Keluarga') === $statusHubungan)>
                                {{ $statusHubungan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap ?: $item->full_name) }}" required>
                </div>

                <div class="field">
                    <label for="nik">NIK</label>
                    <input id="nik" type="text" name="nik" inputmode="numeric" value="{{ old('nik', $item->nik) }}" required>
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
                    <label for="jenis_pekerjaan">Jenis Pekerjaan</label>
                    <input id="jenis_pekerjaan" type="text" name="jenis_pekerjaan" value="{{ old('jenis_pekerjaan', $item->jenis_pekerjaan ?: $item->pekerjaan ?: $item->occupation) }}" required>
                </div>
            </div>

            <h2 style="margin:1.1rem 0 0.85rem;">Status & Dokumen Tambahan</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="status_perkawinan">Status Perkawinan</label>
                    <select id="status_perkawinan" name="status_perkawinan" required>
                        @foreach($statusPerkawinanOptions as $statusPerkawinan)
                            <option value="{{ $statusPerkawinan }}" @selected(old('status_perkawinan', $item->status_perkawinan ?: 'Belum Kawin') === $statusPerkawinan)>
                                {{ $statusPerkawinan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="kewarganegaraan">Kewarganegaraan</label>
                    <select id="kewarganegaraan" name="kewarganegaraan" required>
                        <option value="WNI" @selected(old('kewarganegaraan', $item->kewarganegaraan ?: 'WNI') === 'WNI')>WNI</option>
                        <option value="WNA" @selected(old('kewarganegaraan', $item->kewarganegaraan) === 'WNA')>WNA</option>
                    </select>
                    <small class="muted">Jika WNA, isi minimal No Paspor atau No KITAS/KITAP.</small>
                </div>

                <div class="field">
                    <label for="no_paspor">No Paspor</label>
                    <input id="no_paspor" type="text" name="no_paspor" value="{{ old('no_paspor', $item->no_paspor) }}">
                </div>

                <div class="field">
                    <label for="no_kitas_kitap">No KITAS/KITAP</label>
                    <input id="no_kitas_kitap" type="text" name="no_kitas_kitap" value="{{ old('no_kitas_kitap', $item->no_kitas_kitap) }}">
                </div>

                <div class="field">
                    <label for="nama_ayah">Nama Ayah</label>
                    <input id="nama_ayah" type="text" name="nama_ayah" value="{{ old('nama_ayah', $item->nama_ayah) }}">
                </div>

                <div class="field">
                    <label for="nama_ibu">Nama Ibu</label>
                    <input id="nama_ibu" type="text" name="nama_ibu" value="{{ old('nama_ibu', $item->nama_ibu) }}">
                </div>

                <div class="field">
                    <label for="golongan_darah">Golongan Darah</label>
                    <select id="golongan_darah" name="golongan_darah">
                        <option value="">- Pilih -</option>
                        @foreach($golonganDarahOptions as $gol)
                            <option value="{{ $gol }}" @selected(old('golongan_darah', $item->golongan_darah) === $gol)>{{ $gol }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-records.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
