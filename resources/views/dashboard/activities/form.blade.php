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
                    <label for="title">Judul Kegiatan</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $item->title) }}" required>
                </div>

                <div class="field">
                    <label for="category">Kategori</label>
                    <input id="category" type="text" name="category" value="{{ old('category', $item->category) }}" required>
                </div>

                <div class="field">
                    <label for="activity_date">Tanggal Kegiatan</label>
                    <input id="activity_date" type="date" name="activity_date" value="{{ old('activity_date', $item->activity_date?->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="location">Lokasi</label>
                    <input id="location" type="text" name="location" value="{{ old('location', $item->location) }}" required>
                </div>

                <div class="field">
                    <label for="person_in_charge">Penanggung Jawab</label>
                    <input id="person_in_charge" type="text" name="person_in_charge" value="{{ old('person_in_charge', $item->person_in_charge) }}">
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected(old('status', $item->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="budget">Anggaran (Opsional)</label>
                    <input id="budget" type="number" min="0" step="0.01" name="budget" value="{{ old('budget', $item->budget) }}">
                    <small class="muted">Boleh dikosongkan jika kegiatan tidak memiliki alokasi anggaran.</small>
                </div>

                <div class="field">
                    <label for="cover_image">Cover Kegiatan</label>
                    <input id="cover_image" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                    @if($item->cover_image_url)
                        <small class="muted">File saat ini: <a href="{{ $item->cover_image_url }}" target="_blank">Lihat cover</a></small>
                    @endif
                </div>

                <div class="field">
                    <label for="document">Dokumen Kegiatan</label>
                    <input id="document" type="file" name="document" accept=".pdf,.doc,.docx,.xlsx,.xls,.jpg,.jpeg,.png">
                    @if($item->document_url)
                        <small class="muted">File saat ini: <a href="{{ $item->document_url }}" target="_blank">Lihat dokumen</a></small>
                    @endif
                </div>

                <div class="field full">
                    <label for="summary">Ringkasan Singkat</label>
                    <textarea id="summary" name="summary">{{ old('summary', $item->summary) }}</textarea>
                </div>

                <div class="field full">
                    <label for="description">Deskripsi Lengkap</label>
                    <textarea id="description" name="description">{{ old('description', $item->description) }}</textarea>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.village-activities.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
