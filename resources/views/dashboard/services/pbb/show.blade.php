@extends('layouts.dashboard')

@section('title', 'Detail Pengajuan PBB')
@section('page_title', 'Detail Pengajuan PBB')

@section('content')
    @php
        $ticket = $item->ticket_code;
        if (! $ticket && $item->notes && preg_match('/TIKET:([A-Z0-9\-]+)/i', (string) $item->notes, $matches) === 1) {
            $ticket = strtoupper((string) $matches[1]);
        }

        $waLink = $item->waLink();
        $requestedNops = $item->requestedNopsList();
    @endphp

    <section class="panel">
        <div class="toolbar">
            <h2>Informasi Permohonan</h2>
            <a class="btn btn-secondary" href="{{ route('dashboard.pbb-payment-requests.index') }}">Kembali ke Daftar</a>
        </div>

        <div class="stats">
            <article class="stat-card">
                <small>Nomor Tiket</small>
                <strong>{{ $ticket ?: '-' }}</strong>
            </article>
            <article class="stat-card">
                <small>Pemohon</small>
                <strong>{{ $item->applicant_name }}</strong>
            </article>
            <article class="stat-card">
                <small>Total NOP</small>
                <strong>{{ count($requestedNops) }}</strong>
            </article>
            <article class="stat-card">
                <small>Total Nominal</small>
                <strong>Rp {{ number_format((float) $item->amount_due, 0, ',', '.') }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Kontak Pemohon</h2>
        <p><strong>No. WhatsApp:</strong> {{ $item->phone }}</p>
        <p><strong>Email:</strong> {{ $item->email ?: '-' }}</p>
        <p><strong>Tanggal Pengajuan:</strong> {{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</p>

        @if($waLink)
            <a class="btn btn-primary" href="{{ $waLink }}" target="_blank" rel="noopener">Chat Pemohon via WhatsApp</a>
        @endif
    </section>

    <section class="panel">
        <h2>Rincian NOP</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>NOP</th>
                        <th>Nama Objek Pajak</th>
                        <th>Alamat</th>
                        <th>Tahun</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requestedNops as $nop)
                        <tr>
                            <td>{{ $nop['nop'] ?? '-' }}</td>
                            <td>{{ $nop['tax_name'] ?? '-' }}</td>
                            <td>{{ $nop['address'] ?? '-' }}</td>
                            <td>{{ $nop['tax_year'] ?? '-' }}</td>
                            <td>Rp {{ number_format((float) ($nop['amount_due'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Detail NOP belum tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Update Status</h2>
        <form method="POST" action="{{ route('dashboard.pbb-payment-requests.update', $item) }}" class="service-inline-form" style="max-width:420px;">
            @csrf
            @method('PATCH')
            <select name="status" required>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($item->status === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <input type="text" name="admin_notes" value="{{ $item->admin_notes }}" placeholder="Catatan admin (opsional)">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </section>
@endsection
