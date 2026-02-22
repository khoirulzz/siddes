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
                <div class="field full">
                    <label for="title">Judul</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $item->title) }}" required>
                </div>

                <div class="field full">
                    <label for="image">Foto Galeri</label>
                    <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" {{ $method === 'POST' ? 'required' : '' }}>
                    <small class="muted">Unggah foto. Sistem otomatis mengompres ukuran file agar lebih hemat penyimpanan.</small>
                    @if($item->image_url)
                        <small class="muted">Foto saat ini: <a href="{{ $item->image_url }}" target="_blank">Lihat gambar</a></small>
                    @endif
                    <div class="image-preview-frame{{ $item->image_url ? ' has-image' : '' }}" data-image-preview-frame>
                        <img src="{{ $item->image_url }}" alt="Preview foto galeri" data-image-preview {{ $item->image_url ? '' : 'hidden' }}>
                    </div>
                </div>

                <div class="field">
                    <label for="activity_date">Tanggal Kegiatan</label>
                    <input id="activity_date" type="date" name="activity_date" value="{{ old('activity_date', $item->activity_date?->format('Y-m-d')) }}">
                </div>

                <div class="field full">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description">{{ old('description', $item->description) }}</textarea>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.galleries.index') }}">Kembali</a>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const input = document.getElementById('image');
            const previewFrame = document.querySelector('[data-image-preview-frame]');
            const previewImage = document.querySelector('[data-image-preview]');

            if (!input || !previewFrame || !previewImage) {
                return;
            }

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                const objectUrl = URL.createObjectURL(file);
                previewImage.src = objectUrl;
                previewImage.hidden = false;
                previewFrame.classList.add('has-image');
            });
        })();
    </script>
@endsection
