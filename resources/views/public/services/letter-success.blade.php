@extends('layouts.public')

@section('content')
    <div class="letter-success-page">
        <div class="success-card">
            <p class="success-eyebrow">Pengajuan Berhasil</p>
            <h1>Surat Berhasil Direkam</h1>
            <p class="success-copy">Simpan nomor surat di bawah ini. Nomor ini dipakai untuk pelacakan dan unduh ulang dokumen.</p>

            <div class="ticket-box">{{ $request->official_number ?: $request->ticket_number }}</div>
            <div class="ticket-copy-actions">
                <button
                    type="button"
                    class="btn btn-outline btn-copy-inline"
                    data-copy-value="{{ $request->official_number ?: $request->ticket_number }}"
                >
                    Salin Nomor Surat
                </button>
                <button
                    type="button"
                    class="btn btn-outline btn-copy-inline"
                    data-copy-value="{{ $request->ticket_number }}"
                >
                    Salin Nomor Tiket
                </button>
            </div>

            <div class="success-meta">
                <div><strong>Nomor Tiket:</strong> {{ $request->ticket_number }}</div>
                <div><strong>Jenis Surat:</strong> {{ $request->letter_type }}</div>
                <div><strong>Status Saat Ini:</strong> {{ $request->status }}</div>
            </div>

            <div class="success-actions">
                <a href="{{ route('services.letter.download', ['ticket' => $request->ticket_number, 'format' => 'pdf']) }}" class="btn btn-primary">Download PDF</a>
                <a href="{{ route('services.letter.download', ['ticket' => $request->ticket_number, 'format' => 'docx']) }}" class="btn btn-outline">Download DOCX</a>
                <a href="{{ route('services.letter') }}" class="btn btn-outline">Kembali ke Surat Online</a>
                <a href="{{ route('home') }}" class="btn btn-outline">Kembali ke Beranda</a>
            </div>

            <p class="success-note">Validasi dan tanda tangan tetap dilakukan manual oleh admin desa.</p>
        </div>
    </div>

    <style>
        .letter-success-page {
            padding: 1.4rem 0 2rem;
            display: flex;
            justify-content: center;
        }

        .success-card {
            width: min(760px, 100%);
            border: 1px solid #cce2f7;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f3f9ff 100%);
            box-shadow: 0 18px 34px rgba(18, 67, 109, 0.12);
            padding: 1.4rem;
        }

        .success-eyebrow {
            margin: 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #175585;
            font-weight: 700;
        }

        .success-card h1 {
            margin: 0.35rem 0;
            font-size: 1.65rem;
            color: #0f3d64;
        }

        .success-copy {
            margin: 0;
            color: #3b607f;
        }

        .ticket-box {
            margin-top: 1rem;
            border: 1px dashed #7eaee0;
            border-radius: 0.75rem;
            background: #eef6ff;
            padding: 0.8rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: #0f4c81;
            text-align: center;
            letter-spacing: 0.04em;
        }

        .success-meta {
            margin-top: 0.9rem;
            display: grid;
            gap: 0.35rem;
            color: #274d70;
            font-size: 0.92rem;
        }

        .ticket-copy-actions {
            margin-top: 0.7rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .success-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .success-note {
            margin: 0.9rem 0 0;
            color: #5f7590;
            font-size: 0.85rem;
        }
    </style>
@endsection
