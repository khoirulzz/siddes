@extends('layouts.dashboard')

@section('title', 'Arsip Layanan')
@section('page_title', 'Arsip Layanan')

@section('content')
    <section class="panel">
        <h2>Arsip Layanan Digital</h2>
        <div class="stats">
            <article class="stat-card">
                <small>Arsip Surat</small>
                <strong>{{ $stats['surat'] }}</strong>
            </article>
            <article class="stat-card">
                <small>Arsip PBB</small>
                <strong>{{ $stats['pbb'] }}</strong>
            </article>
            <article class="stat-card">
                <small>Arsip Pengaduan</small>
                <strong>{{ $stats['pengaduan'] }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="tabs">
            <a class="tab {{ $type === 'surat' ? 'active' : '' }}" href="{{ route('dashboard.service-archives.index', ['type' => 'surat']) }}">Arsip Surat</a>
            <a class="tab {{ $type === 'pbb' ? 'active' : '' }}" href="{{ route('dashboard.service-archives.index', ['type' => 'pbb']) }}">Arsip PBB</a>
            <a class="tab {{ $type === 'pengaduan' ? 'active' : '' }}" href="{{ route('dashboard.service-archives.index', ['type' => 'pengaduan']) }}">Arsip Pengaduan</a>
        </div>

        <form method="GET" action="{{ route('dashboard.service-archives.index') }}" class="inline-form">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="field">
                <label for="q">Pencarian</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari tiket, nomor, nama, NIK, atau WA">
            </div>
            @if($type !== 'surat')
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua Status</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.service-archives.index', ['type' => $type]) }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        @if($type === 'surat')
            <h2>Daftar Arsip Surat</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor Resmi</th>
                            <th>Tiket</th>
                            <th>Tanggal</th>
                            <th>Pemohon</th>
                            <th>Jenis Surat</th>
                            <th>File Surat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->official_number ?: '-' }}</td>
                                <td>{{ $item->ticket_number }}</td>
                                <td>{{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</td>
                                <td>
                                    {{ $item->applicant_name ?: '-' }}<br>
                                    <small>{{ $item->nik ?: '-' }}</small>
                                </td>
                                <td>{{ $item->letter_type }}</td>
                                <td>
                                    <a class="btn btn-secondary" target="_blank" href="{{ route('dashboard.service-archives.letters.pdf', ['letterServiceRequest' => $item, 'mode' => 'view']) }}">Lihat File</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Belum ada data arsip surat selesai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($type === 'pbb')
            <h2>Daftar Arsip PBB</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tiket</th>
                            <th>Tanggal</th>
                            <th>Pemohon</th>
                            <th>Kontak</th>
                            <th>Total NOP</th>
                            <th>Total Nominal</th>
                            <th>Status</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $ticket = $item->ticket_code;
                                if (! $ticket && $item->notes && preg_match('/TIKET:([A-Z0-9\-]+)/i', (string) $item->notes, $matches) === 1) {
                                    $ticket = strtoupper((string) $matches[1]);
                                }
                            @endphp
                            <tr>
                                <td>{{ $ticket ?: '-' }}</td>
                                <td>{{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</td>
                                <td>{{ $item->applicant_name }}</td>
                                <td>{{ $item->phone }}</td>
                                <td>{{ $item->totalNops() }}</td>
                                <td>Rp {{ number_format((float) $item->amount_due, 0, ',', '.') }}</td>
                                <td><span class="status-badge">{{ $item->status }}</span></td>
                                <td>
                                    @if($item->proof_url)
                                        <a class="btn btn-secondary" href="{{ $item->proof_url }}" target="_blank">Lihat Bukti</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">Belum ada data arsip PBB.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <h2>Daftar Arsip Pengaduan</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tiket</th>
                            <th>Tanggal</th>
                            <th>Pelapor</th>
                            <th>Kategori</th>
                            <th>Subjek</th>
                            <th>Status</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->ticket_code }}</td>
                                <td>{{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</td>
                                <td>
                                    {{ $item->reporter_name }}<br>
                                    <small>NIK: {{ $item->nik ?: '-' }}</small>
                                </td>
                                <td>{{ $item->category }}</td>
                                <td>{{ $item->subject }}</td>
                                <td><span class="status-badge">{{ $item->status }}</span></td>
                                <td>
                                    @if($item->evidence_url)
                                        <a class="btn btn-secondary" href="{{ route('dashboard.complaint-reports.evidence', $item) }}" target="_blank">Lihat Bukti</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Belum ada data arsip pengaduan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="toolbar" style="margin-top:0.85rem;">
            <small class="muted">
                Menampilkan {{ $items->count() }} data dari total {{ $items->total() }}.
            </small>
            <div class="actions">
                @if($items->previousPageUrl())
                    <a class="btn btn-secondary" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
                @endif
                @if($items->nextPageUrl())
                    <a class="btn btn-secondary" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
                @endif
            </div>
        </div>
    </section>
@endsection
