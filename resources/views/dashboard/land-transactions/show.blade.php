@extends('layouts.dashboard')

@section('title', 'Detail Transaksi Pertanahan')
@section('page_title', 'Detail Transaksi Pertanahan')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <div>
                <h2>{{ $item->transaction_number }}</h2>
                <p class="muted" style="margin:0.2rem 0 0;">{{ $item->type_label }} · {{ $item->transaction_date?->format('d-m-Y') ?? '-' }}</p>
            </div>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.edit', $item) }}">Edit</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.index') }}">Kembali</a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <tbody>
                    <tr>
                        <th style="width:260px;">Nomor Dokumen</th>
                        <td>{{ $item->document_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pihak A (Penjual/Pengalih)</th>
                        <td>
                            <a href="{{ route('dashboard.land-transactions.history', ['name' => $item->party_a_name, 'page' => $item->party_a_page]) }}">
                                {{ $item->party_a_name }} (Hal. {{ $item->party_a_page }})
                            </a>
                            @if($item->party_a_identifier)
                                <br>
                                <small class="muted">Istri/Pengenal: {{ $item->party_a_identifier }}</small>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Alamat Pihak A</th>
                        <td>{{ $item->party_a_address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pihak B (Pembeli/Penerima)</th>
                        <td>
                            <a href="{{ route('dashboard.land-transactions.history', ['name' => $item->party_b_name, 'page' => $item->party_b_page]) }}">
                                {{ $item->party_b_name }} (Hal. {{ $item->party_b_page }})
                            </a>
                            @if($item->party_b_identifier)
                                <br>
                                <small class="muted">Istri/Pengenal: {{ $item->party_b_identifier }}</small>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Alamat Pihak B</th>
                        <td>{{ $item->party_b_address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Luas Tercatat</th>
                        <td>{{ $item->area_m2 !== null ? number_format((float) $item->area_m2, 2, ',', '.') . ' m2' : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Objek Tanah</th>
                        <td>{{ $item->land_object }}</td>
                    </tr>
                    <tr>
                        <th>Catatan</th>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="toolbar">
            <h2>Arsip Dokumen</h2>
            <small class="muted">{{ number_format($item->files->count(), 0, ',', '.') }} file</small>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Uploader</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($item->files as $file)
                        <tr>
                            <td>{{ $file->original_name ?: basename((string) $file->file_path) }}</td>
                            <td>{{ $file->mime_type ?: '-' }}</td>
                            <td>{{ $file->size_bytes ? number_format($file->size_bytes / 1024, 1, ',', '.') . ' KB' : '-' }}</td>
                            <td>{{ $file->uploader?->name ?: '-' }}</td>
                            <td>{{ $file->created_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.files.show', $file) }}" target="_blank">Lihat</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.files.show', ['landTransactionFile' => $file, 'mode' => 'download']) }}">Unduh</a>
                                    <form action="{{ route('dashboard.land-transactions.files.destroy', $file) }}" method="POST" onsubmit="return confirm('Hapus file arsip ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada arsip dokumen pada transaksi ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Riwayat Terkait Halaman Pihak</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Pihak A</th>
                        <th>Pihak B</th>
                        <th>Arsip</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($related as $row)
                        <tr>
                            <td>{{ $row->transaction_number }}</td>
                            <td>{{ $row->transaction_date?->format('d-m-Y') ?: '-' }}</td>
                            <td>{{ $row->type_label }}</td>
                            <td>
                                {{ $row->party_a_name }} (Hal. {{ $row->party_a_page }})
                                @if($row->party_a_identifier)
                                    <br>
                                    <small class="muted">{{ $row->party_a_identifier }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $row->party_b_name }} (Hal. {{ $row->party_b_page }})
                                @if($row->party_b_identifier)
                                    <br>
                                    <small class="muted">{{ $row->party_b_identifier }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($row->files_count ?? 0, 0, ',', '.') }} file</td>
                            <td><a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.show', $row) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Belum ada transaksi terkait halaman ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
