@extends('layouts.dashboard')

@section('title', 'Detail Pengaduan')
@section('page_title', 'Detail Pengaduan Masyarakat')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Informasi Pengaduan</h2>
            <a class="btn btn-secondary" href="{{ route('dashboard.complaint-reports.index') }}">Kembali ke Daftar</a>
        </div>

        <div class="stats">
            <article class="stat-card">
                <small>Nomor Tiket</small>
                <strong>{{ $item->ticket_code }}</strong>
            </article>
            <article class="stat-card">
                <small>Pelapor</small>
                <strong>{{ $item->reporter_name }}</strong>
            </article>
            <article class="stat-card">
                <small>Status</small>
                <strong>{{ $item->status }}</strong>
            </article>
            <article class="stat-card">
                <small>Tanggal Lapor</small>
                <strong>{{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Data Pelapor</h2>
        <p><strong>NIK:</strong> {{ $item->nik ?: '-' }}</p>
        <p><strong>No. WhatsApp:</strong> {{ $item->phone ?: '-' }}</p>
        <p><strong>Email:</strong> {{ $item->email ?: '-' }}</p>
    </section>

    <section class="panel">
        <h2>Isi Pengaduan</h2>
        <p><strong>Kategori:</strong> {{ $item->category }}</p>
        <p><strong>Subjek:</strong> {{ $item->subject }}</p>
        <p><strong>Lokasi:</strong> {{ $item->location ?: '-' }}</p>
        <div>
            <strong>Deskripsi:</strong>
            <p style="margin-top:0.35rem;">{{ $item->description }}</p>
        </div>
        <p>
            <strong>Lampiran:</strong>
            @if($item->evidence_url)
                <a class="btn btn-secondary" href="{{ route('dashboard.complaint-reports.evidence', $item) }}" target="_blank">Lihat Bukti</a>
            @else
                -
            @endif
        </p>
    </section>

    <section class="panel">
        <h2>Update Status</h2>
        <form method="POST" action="{{ route('dashboard.complaint-reports.update', $item) }}" class="service-inline-form" style="max-width:520px;">
            @csrf
            @method('PATCH')
            <select name="status" required>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($item->status === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <input type="text" name="response" value="{{ $item->response }}" placeholder="Respon singkat (opsional)">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </section>
@endsection

