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
                    <label for="transaction_date">Tanggal Transaksi</label>
                    <input id="transaction_date" type="date" name="transaction_date" value="{{ old('transaction_date', optional($item->transaction_date)->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="transaction_type">Jenis Transaksi</label>
                    <select id="transaction_type" name="transaction_type" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('transaction_type', $item->transaction_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="document_number">Nomor Dokumen (AJB/Surat)</label>
                    <input id="document_number" type="text" name="document_number" value="{{ old('document_number', $item->document_number) }}" placeholder="Opsional">
                </div>

                <div class="field">
                    <label for="area_m2">Luas Tercatat (m2)</label>
                    <input id="area_m2" type="number" min="0" step="0.01" name="area_m2" value="{{ old('area_m2', $item->area_m2) }}" placeholder="Opsional">
                </div>

                <div class="field">
                    <label for="party_a_name">Pihak A (Penjual/Pengalih)</label>
                    <input id="party_a_name" type="text" name="party_a_name" value="{{ old('party_a_name', $item->party_a_name) }}" required>
                </div>

                <div class="field">
                    <label for="party_a_identifier">Istri/Pengenal Pihak A (Opsional)</label>
                    <input id="party_a_identifier" type="text" name="party_a_identifier" value="{{ old('party_a_identifier', $item->party_a_identifier) }}" placeholder="Nama istri atau pengenal lain">
                </div>

                <div class="field">
                    <label for="party_a_page">Halaman Buku C Pihak A</label>
                    <input id="party_a_page" type="text" name="party_a_page" value="{{ old('party_a_page', $item->party_a_page) }}" placeholder="Contoh: 600" required>
                </div>

                <div class="field full">
                    <label for="party_a_address">Alamat Pihak A</label>
                    <textarea id="party_a_address" name="party_a_address" required placeholder="Alamat lengkap pihak A ditulis manual">{{ old('party_a_address', $item->party_a_address) }}</textarea>
                </div>

                <div class="field">
                    <label for="party_b_name">Pihak B (Pembeli/Penerima)</label>
                    <input id="party_b_name" type="text" name="party_b_name" value="{{ old('party_b_name', $item->party_b_name) }}" required>
                </div>

                <div class="field">
                    <label for="party_b_identifier">Istri/Pengenal Pihak B (Opsional)</label>
                    <input id="party_b_identifier" type="text" name="party_b_identifier" value="{{ old('party_b_identifier', $item->party_b_identifier) }}" placeholder="Nama istri atau pengenal lain">
                </div>

                <div class="field">
                    <label for="party_b_page">Halaman Buku C Pihak B</label>
                    <input id="party_b_page" type="text" name="party_b_page" value="{{ old('party_b_page', $item->party_b_page) }}" placeholder="Contoh: 500" required>
                </div>

                <div class="field full">
                    <label for="party_b_address">Alamat Pihak B</label>
                    <textarea id="party_b_address" name="party_b_address" required placeholder="Alamat lengkap pihak B ditulis manual">{{ old('party_b_address', $item->party_b_address) }}</textarea>
                </div>

                <div class="field full">
                    <label for="land_object">Ringkasan Objek Tanah</label>
                    <textarea id="land_object" name="land_object" required placeholder="Lokasi, batas, keterangan bidang tanah, dll">{{ old('land_object', $item->land_object) }}</textarea>
                </div>

                <div class="field full">
                    <label for="notes">Catatan Transaksi</label>
                    <textarea id="notes" name="notes" placeholder="Opsional">{{ old('notes', $item->notes) }}</textarea>
                </div>

                <div class="field full">
                    <label for="files">Upload Bukti (PDF/Gambar, bisa lebih dari satu)</label>
                    <input id="files" type="file" name="files[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple>
                    <small class="muted">Maksimal 10 MB per file.</small>
                </div>
            </div>

            @if($item->exists && $item->relationLoaded('files') && $item->files->isNotEmpty())
                <div class="panel" style="margin-top:0.9rem;">
                    <h2>Arsip Dokumen Saat Ini</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Tipe</th>
                                    <th>Ukuran</th>
                                    <th>Aksi</th>
                                    <th>Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($item->files as $file)
                                    <tr>
                                        <td>{{ $file->original_name ?: basename((string) $file->file_path) }}</td>
                                        <td>{{ $file->mime_type ?: '-' }}</td>
                                        <td>{{ $file->size_bytes ? number_format($file->size_bytes / 1024, 1, ',', '.') . ' KB' : '-' }}</td>
                                        <td>
                                            <a href="{{ route('dashboard.land-transactions.files.show', $file) }}" target="_blank">Lihat</a>
                                            |
                                            <a href="{{ route('dashboard.land-transactions.files.show', ['landTransactionFile' => $file, 'mode' => 'download']) }}">Unduh</a>
                                        </td>
                                        <td>
                                            <label style="display:inline-flex;gap:0.35rem;align-items:center;">
                                                <input type="checkbox" name="remove_file_ids[]" value="{{ $file->id }}">
                                                <span>Tandai</span>
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="muted">File yang ditandai akan dihapus saat klik Simpan.</small>
                </div>
            @endif

            <div class="actions" style="margin-top:0.9rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
