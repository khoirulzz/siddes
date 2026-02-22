@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    <section class="panel">
        <form method="POST" action="{{ $route }}" enctype="multipart/form-data">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="form-grid">
                <div class="field">
                    <label for="land_code">Kode Tanah</label>
                    <input id="land_code" type="text" name="land_code" value="{{ old('land_code', $item->land_code) }}">
                </div>

                <div class="field">
                    <label for="location">Lokasi</label>
                    <input id="location" type="text" name="location" value="{{ old('location', $item->location) }}" required>
                </div>

                <div class="field">
                    <label for="hamlet">Dusun</label>
                    <select id="hamlet" name="hamlet">
                        <option value="">Pilih Dusun</option>
                        @foreach($hamlets as $hamlet)
                            <option value="{{ $hamlet }}" @selected(old('hamlet', $item->hamlet) === $hamlet)>
                                {{ $hamlet }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="category">Kategori</label>
                    <input id="category" type="text" name="category" value="{{ old('category', $item->category) }}" required>
                </div>

                <div class="field">
                    <label for="area_m2">Luas (m2)</label>
                    <input id="area_m2" type="number" min="0" step="0.01" name="area_m2" value="{{ old('area_m2', $item->area_m2 ?? 0) }}" required>
                </div>

                <div class="field">
                    <label for="ownership_status">Status Kepemilikan</label>
                    <input id="ownership_status" type="text" name="ownership_status" value="{{ old('ownership_status', $item->ownership_status) }}" required>
                </div>

                <div class="field">
                    <label for="owner_name">Nama Pemilik/Pengguna</label>
                    <input id="owner_name" type="text" name="owner_name" value="{{ old('owner_name', $item->owner_name) }}">
                </div>

                <div class="field">
                    <label for="certificate_number">Nomor Sertifikat</label>
                    <input id="certificate_number" type="text" name="certificate_number" value="{{ old('certificate_number', $item->certificate_number) }}">
                </div>

                <div class="field">
                    <label for="tax_object_number">Nomor Objek Pajak (NOP)</label>
                    <input id="tax_object_number" type="text" name="tax_object_number" value="{{ old('tax_object_number', $item->tax_object_number) }}">
                </div>

                <div class="field">
                    <label for="status">Status Data</label>
                    <input id="status" type="text" name="status" value="{{ old('status', $item->status) }}" required>
                </div>

                <div class="field">
                    <label for="photo">Upload Foto Lahan</label>
                    <input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                    @if($item->photo_url)
                        <small class="muted">File saat ini: <a href="{{ $item->photo_url }}" target="_blank">Lihat foto</a></small>
                    @endif
                </div>

                <div class="field">
                    <label for="document">Upload Dokumen Pertanahan</label>
                    <input id="document" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    @if($item->document_url)
                        <small class="muted">File saat ini: <a href="{{ $item->document_url }}" target="_blank">Lihat dokumen</a></small>
                    @endif
                </div>

                <div class="field full">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description">{{ old('description', $item->description) }}</textarea>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.land-records.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
