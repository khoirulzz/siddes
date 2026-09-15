@extends('layouts.dashboard')

@section('title', 'Master Data PBB')
@section('page_title', 'Master Data PBB')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Master Data PBB per Tahun</h2>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('dashboard.pbb-tax-objects.create') }}">Tambah Data (+)</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.template') }}">Download Template</a>
            </div>
        </div>

        <div class="message is-feedback" data-import-feedback hidden></div>

        <form data-import-form data-preview-url="{{ route('dashboard.pbb-tax-objects.import.preview') }}" data-commit-url="{{ route('dashboard.pbb-tax-objects.import.commit') }}" enctype="multipart/form-data" class="inline-form">
            @csrf
            <div class="field">
                <label for="file">File Data PBB (Excel/CSV)</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                <small class="muted">Maksimal 10.000 baris. Gunakan file template PBB.</small>
            </div>
            <div class="field">
                <label for="year_override">Override Tahun Pajak (Opsional)</label>
                <input id="year_override" type="number" name="year_override" min="2026" max="{{ date('Y') + 1 }}" placeholder="Pilih tahun">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit" data-action="preview">Periksa File (Pratinjau)</button>
                <button class="btn btn-secondary" type="button" data-action="reset">Batal</button>
            </div>
        </form>

        <div data-preview-box hidden class="import-preview-box" style="margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 1.5rem;">
            <div class="import-preview-summary" style="margin-bottom: 1rem;">
                <p><strong>Hasil Pemeriksaan:</strong> <span data-summary-text></span></p>
                <div class="actions" style="margin-top: 0.5rem;">
                    <button class="btn btn-primary" data-action="commit">Import Baris Valid</button>
                    <button class="btn btn-secondary" data-action="report" hidden>Unduh Laporan Kegagalan</button>
                </div>
            </div>
            <div class="table-wrap" style="max-height: 400px; overflow-y: auto;">
                <table class="is-compact">
                    <thead>
                        <tr>
                            <th>Baris</th>
                            <th>Status</th>
                            <th>Tahun</th>
                            <th>NOP</th>
                            <th>Nama WP</th>
                            <th>PBB Terhutang</th>
                            <th>Pesan Kesalahan</th>
                        </tr>
                    </thead>
                    <tbody data-preview-table></tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.pbb-tax-objects.destroy-year') }}" class="inline-form" onsubmit="return confirm('Yakin hapus semua data PBB pada tahun yang dipilih? Proses ini tidak bisa dibatalkan.');">
            @csrf
            @method('DELETE')
            <div class="field">
                <label for="delete_year">Hapus Semua Data per Tahun</label>
                <select id="delete_year" name="year" required {{ $availableYears->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Pilih Tahun</option>
                    @foreach($availableYears as $availableYear)
                        <option value="{{ $availableYear }}" {{ (int) ($filters['year'] ?? 0) === (int) $availableYear ? 'selected' : '' }}>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-danger" type="submit" {{ $availableYears->isEmpty() ? 'disabled' : '' }}>Hapus Data Tahun Terpilih</button>
        </form>
    </section>

    <section class="panel">
        <h2>Filter Data</h2>
        <form method="GET" action="{{ route('dashboard.pbb-tax-objects.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari NOP / Nama WP / Jalan WP / Jalan OP</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: 33.26.010.004.001-0028.0">
            </div>
            <div class="field">
                <label for="year">Tahun</label>
                <input id="year" type="number" name="year" value="{{ $filters['year'] ?? '' }}" min="2026" max="{{ date('Y') + 1 }}" placeholder="Semua tahun">
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Tabel Master Data PBB</h2>
        <div class="table-wrap population-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TAHUN</th>
                        <th>NOMOR OBJEK PAJAK (NOP)</th>
                        <th>NAMA WP SPPT</th>
                        <th>JALAN WP SPPT</th>
                        <th>RT WP SPPT</th>
                        <th>RW WP SPPT</th>
                        <th>DESA WP SPPT</th>
                        <th>JALAN OP SPPT</th>
                        <th>RT OP SPPT</th>
                        <th>RW OP SPPT</th>
                        <th>LUAS TANAH SPPT</th>
                        <th>LUAS BANGUNAN SPPT</th>
                        <th>PBB TERHUTANG</th>
                        <th>TANGGAL PEMBAYARAN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxObjects as $item)
                        <tr>
                            <td>{{ ($taxObjects->firstItem() ?? 0) + $loop->index }}</td>
                            <td>{{ $item->resolvedTaxYear() }}</td>
                            <td>{{ $item->nop }}</td>
                            <td>{{ $item->nama_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->jalan_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->rt_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->rw_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->desa_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->jalan_op_sppt ?: '-' }}</td>
                            <td>{{ $item->rt_op_sppt ?: '-' }}</td>
                            <td>{{ $item->rw_op_sppt ?: '-' }}</td>
                            <td>{{ number_format($item->resolvedLandArea(), 2, ',', '.') }}</td>
                            <td>{{ number_format($item->resolvedBuildingArea(), 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->resolvedAmountDue(), 0, ',', '.') }}</td>
                            <td>{{ $item->resolvedPaidAt()?->format('d-m-Y') ?: '-' }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.show', $item) }}">Detail</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.edit', $item) }}">Edit</a>
                                    <form method="POST" action="{{ route('dashboard.pbb-tax-objects.destroy', $item) }}" onsubmit="return confirm('Yakin hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="16">Belum ada data PBB.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($taxObjects->hasPages())
            <div class="table-pagination">
                <small class="muted">
                    Menampilkan {{ $taxObjects->firstItem() ?? 0 }} - {{ $taxObjects->lastItem() ?? 0 }} dari {{ $taxObjects->total() }} data
                </small>
                <div class="pager-controls">
                    @if($taxObjects->onFirstPage())
                        <span class="pager-link is-disabled">Sebelumnya</span>
                    @else
                        <a class="pager-link" href="{{ $taxObjects->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    <span class="pager-meta">Halaman {{ $taxObjects->currentPage() }} / {{ $taxObjects->lastPage() }}</span>

                    @if($taxObjects->hasMorePages())
                        <a class="pager-link" href="{{ $taxObjects->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="pager-link is-disabled">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection

@section('scripts')
    <script>
        (function() {
            const form = document.querySelector('[data-import-form]');
            const previewBox = document.querySelector('[data-preview-box]');
            const summaryText = document.querySelector('[data-summary-text]');
            const previewTable = document.querySelector('[data-preview-table]');
            const previewButton = form?.querySelector('[data-action="preview"]');
            const resetButton = form?.querySelector('[data-action="reset"]');
            const commitButton = previewBox?.querySelector('[data-action="commit"]');
            const reportButton = previewBox?.querySelector('[data-action="report"]');
            const feedback = document.querySelector('[data-import-feedback]');

            let previewToken = null;
            let previewData = null;
            let importBusy = false;

            const showFeedback = (message, type) => {
                if (!feedback) return;
                feedback.textContent = message;
                feedback.className = `message is-feedback is-${type}`;
                feedback.hidden = false;
            };

            const startProgress = (isCommit) => {
                importBusy = true;
                const active = isCommit ? commitButton : previewButton;
                const originalText = active.textContent;
                active.textContent = 'Memproses...';
                active.disabled = true;
                if (!isCommit && resetButton) resetButton.disabled = true;
                if (isCommit && reportButton) reportButton.disabled = true;
                return () => {
                    importBusy = false;
                    active.textContent = originalText;
                    active.disabled = false;
                    if (!isCommit && resetButton) resetButton.disabled = false;
                    if (isCommit && reportButton) reportButton.disabled = false;
                };
            };

            const responseMessage = async (response) => {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error(response.status >= 500 ? 'Server mengalami kendala. Silakan coba beberapa saat lagi.' : 'Sesi Anda mungkin telah berakhir, silakan muat ulang halaman.');
                }
                const data = await response.json();
                const text = data.message || Object.values(data.errors || {})[0]?.[0] || 'Terjadi kesalahan sistem.';
                return { message: text, data };
            };

            const renderPreview = (payload, token) => {
                previewToken = token;
                previewData = payload;
                const summary = payload.summary;
                summaryText.textContent = `${summary.valid} data siap diimpor (${summary.inserted} baru, ${summary.updated} diperbarui, ${summary.unchanged} tidak berubah), ${summary.invalid} dilewati karena format tidak sesuai.`;
                
                commitButton.disabled = summary.valid < 1;
                previewTable.innerHTML = '';
                
                payload.rows.forEach((row) => {
                    const tr = document.createElement('tr');
                    const issues = row.issues.map((i) => `<span class="tag is-${i.severity === 'error' ? 'danger' : 'warning'}">${i.message}</span>`).join(' ');
                    
                    const statusText = row.status === 'invalid' ? '<span class="tag is-danger">Dilewati (Error)</span>' : 
                                       row.status === 'new' ? '<span class="tag is-success">Baru</span>' :
                                       row.status === 'update' ? '<span class="tag is-info">Update</span>' :
                                       '<span class="tag is-light">Tetap</span>';

                    tr.innerHTML = `
                        <td>${row.row}</td>
                        <td>${statusText}</td>
                        <td>${row.tax_year || '-'}</td>
                        <td>${row.nop || '-'}</td>
                        <td>${row.nama_wp_sppt || '-'}</td>
                        <td>${issues || '-'}</td>
                    `;
                    previewTable.appendChild(tr);
                });

                reportButton.hidden = !payload.rows.some((row) => row.issues.length);
                previewBox.hidden = false;
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (importBusy || !form.reportValidity()) return;
                const body = new FormData(form);
                previewToken = null;
                previewData = null;
                previewBox.hidden = true;
                const finishProgress = startProgress(false);
                try {
                    const response = await fetch(form.dataset.previewUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                    const result = await responseMessage(response);
                    if (!response.ok) throw new Error(result.message);
                    if (!result.data.preview || !result.data.token) throw new Error('Hasil pemeriksaan tidak dapat dibaca. Periksa file sekali lagi.');
                    renderPreview(result.data.preview, result.data.token);
                    showFeedback(result.message, result.data.preview.summary.invalid > 0 ? 'warning' : 'success');
                } catch (error) {
                    showFeedback(error instanceof TypeError ? 'Koneksi terputus saat memeriksa file.' : error.message, 'error');
                } finally {
                    finishProgress();
                }
            });

            commitButton?.addEventListener('click', async () => {
                if (importBusy || !previewToken || !previewData || previewData.summary.valid < 1) return;
                const body = new FormData(form);
                body.append('preview_token', previewToken);
                const finishProgress = startProgress(true);
                try {
                    const response = await fetch(form.dataset.commitUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                    const result = await responseMessage(response);
                    if (response.status === 409 && result.data.preview) {
                        renderPreview(result.data.preview, result.data.token);
                        showFeedback(result.message, 'warning');
                        return;
                    }
                    if (!response.ok) throw new Error(result.message);
                    if (!result.data.result || !result.data.redirect) throw new Error('Jawaban server tidak lengkap.');
                    previewToken = null;
                    showFeedback(result.message, 'success');
                    window.setTimeout(() => window.location.assign(result.data.redirect), 1800);
                } catch (error) {
                    previewToken = null;
                    showFeedback(error instanceof TypeError ? 'Koneksi terputus saat menyimpan.' : error.message, 'error');
                } finally {
                    finishProgress();
                }
            });

            resetButton?.addEventListener('click', () => {
                form.reset();
                previewToken = null;
                previewData = null;
                previewBox.hidden = true;
                feedback.hidden = true;
            });

            form?.querySelectorAll('input[type="file"], select, input[type="number"]').forEach((control) => {
                control.addEventListener('change', () => {
                    if (!previewToken) return;
                    previewToken = null;
                    previewData = null;
                    previewBox.hidden = true;
                    showFeedback('File atau pilihan tahun berubah. Jalankan pemeriksaan ulang.', 'warning');
                });
            });

            reportButton?.addEventListener('click', () => {
                if (!previewData) return;
                const rows = [['baris', 'nop', 'tahun', 'nama_wp', 'status', 'kolom', 'tingkat', 'pesan', 'saran_perbaikan']];
                previewData.rows.forEach((row) => row.issues.forEach((issue) => rows.push([
                    row.row, row.nop, row.tax_year, row.nama_wp_sppt, row.status, issue.field, issue.severity, issue.message, issue.hint,
                ])));
                const csvCell = (value) => {
                    let text = String(value ?? '');
                    if (/^[=+\-@]/.test(text)) text = `'${text}`;
                    return `"${text.replaceAll('"', '""')}"`;
                };
                const blob = new Blob(['\uFEFF' + rows.map((row) => row.map(csvCell).join(';')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'laporan-validasi-pbb.csv';
                link.click();
                URL.revokeObjectURL(link.href);
            });
        })();
    </script>
@endsection
