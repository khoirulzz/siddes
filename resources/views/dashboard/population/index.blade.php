@extends('layouts.dashboard')

@section('title', 'Manajemen Kependudukan')
@section('page_title', 'Manajemen Kependudukan')

@section('content')
    <section class="panel population-hero">
        <div class="population-hero__content">
            <div>
                <span class="eyebrow">Administrasi Penduduk</span>
                <h2>Data keluarga yang lebih mudah dibaca dan diperbarui</h2>
                <p class="muted">Mulai dari daftar KK, buka anggota keluarga saat diperlukan, atau beralih ke daftar individu.</p>
            </div>
            <div class="actions population-hero__actions">
                <a class="btn btn-primary" href="{{ route('dashboard.population-records.create', ['mode' => 'household']) }}">Tambah KK Baru</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-records.create') }}">Tambah Penduduk</a>
                <button class="btn btn-secondary" type="button" data-open-import>Import Data</button>
            </div>
        </div>

        <div class="population-stats">
            <article class="population-stat population-stat--family">
                <span>KK ditemukan</span>
                <strong>{{ number_format($filteredHouseholdTotal, 0, ',', '.') }}</strong>
            </article>
            <article class="population-stat population-stat--people">
                <span>Penduduk ditemukan</span>
                <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong>
            </article>
            <article class="population-stat population-stat--male">
                <span>Laki-laki</span>
                <strong>{{ number_format($genderSummary['Laki-laki'], 0, ',', '.') }}</strong>
            </article>
            <article class="population-stat population-stat--female">
                <span>Perempuan</span>
                <strong>{{ number_format($genderSummary['Perempuan'], 0, ',', '.') }}</strong>
            </article>
        </div>
    </section>

    <details class="panel population-import" id="populationImportPanel">
        <summary>
            <span>
                <strong>Import Excel atau CSV</strong>
                <small>Pratinjau dan periksa kesalahan sebelum data disimpan.</small>
            </span>
            <span class="details-action">Buka import</span>
        </summary>

        <div class="population-import__body">
            <div class="import-guide">
                <div>
                    <strong>Gunakan template Excel terbaru</strong>
                    <p>NIK dan No. KK sudah disiapkan sebagai teks agar tidak dibulatkan Excel.</p>
                </div>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ route('dashboard.population-records.template') }}">Unduh XLSX</a>
                    <a class="btn btn-secondary" href="{{ route('dashboard.population-records.template', ['format' => 'csv']) }}">Unduh CSV</a>
                </div>
            </div>

            <form
                id="populationImportForm"
                class="import-form"
                data-preview-url="{{ route('dashboard.population-records.import.preview') }}"
                data-commit-url="{{ route('dashboard.population-records.import') }}"
            >
                @csrf
                <div class="field">
                    <label for="populationImportFile">File data</label>
                    <input id="populationImportFile" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                    <small class="muted">XLSX, XLS, CSV, atau TXT · maksimal 15 MB dan 10.000 baris.</small>
                </div>
                <div class="field">
                    <label for="populationHamletOverride">Tetapkan dusun untuk semua baris <span class="muted">(opsional)</span></label>
                    <select id="populationHamletOverride" name="hamlet_override">
                        <option value="">Ikuti isi file</option>
                        @foreach($hamlets as $hamlet)
                            <option value="{{ $hamlet }}">{{ $hamlet }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions import-form__actions">
                    <button class="btn btn-primary" type="submit" data-preview-button>Periksa File</button>
                    <button class="btn btn-secondary" type="button" data-reset-import>Reset</button>
                </div>
            </form>

            <div class="import-feedback" data-import-feedback hidden role="status"></div>

            <section class="import-preview" data-import-preview hidden aria-live="polite">
                <div class="toolbar">
                    <div>
                        <h3>Hasil pratinjau</h3>
                        <p class="muted" data-preview-meta></p>
                    </div>
                    <div class="actions">
                        <button class="btn btn-secondary" type="button" data-download-report>Unduh Laporan</button>
                        <button class="btn btn-primary" type="button" data-commit-import>Import Baris Valid</button>
                    </div>
                </div>
                <div class="import-summary" data-import-summary></div>
                <div class="table-wrap import-preview__table">
                    <table>
                        <thead>
                            <tr class="clickable-row" data-row-link="{{ route('dashboard.population-households.show', $household) }}" tabindex="0">
                                <th>Baris</th>
                                <th>Nama / NIK</th>
                                <th>No. KK</th>
                                <th>Tindakan</th>
                                <th>Hasil pemeriksaan</th>
                            </tr>
                        </thead>
                        <tbody data-preview-rows></tbody>
                    </table>
                </div>
            </section>

            @if($recentImports->isNotEmpty())
                <details class="import-history">
                    <summary>Riwayat 5 import terakhir</summary>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Waktu</th><th>File</th><th>Petugas</th><th>Status</th><th>Ringkasan</th></tr></thead>
                            <tbody>
                                @foreach($recentImports as $run)
                                    @php
                                        $runStatusClass = match ($run->status) {
                                            'completed' => 'success',
                                            'processing' => 'update',
                                            default => 'danger',
                                        };
                                        $runStatusLabel = match ($run->status) {
                                            'completed' => 'Selesai',
                                            'processing' => 'Diproses',
                                            default => 'Gagal',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $run->created_at->format('d-m-Y H:i') }}</td>
                                        <td>{{ $run->original_filename }}</td>
                                        <td>{{ $run->user?->name ?: '-' }}</td>
                                        <td><span class="status-pill status-pill--{{ $runStatusClass }}">{{ $runStatusLabel }}</span></td>
                                        <td>{{ $run->residents_created }} baru · {{ $run->residents_updated }} diperbarui · {{ $run->invalid_rows }} dilewati</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    </details>

    <section class="panel population-workspace">
        <div class="population-tabs" role="navigation" aria-label="Jenis tampilan kependudukan">
            <a class="population-tab {{ $viewMode === 'kk' ? 'active' : '' }}" href="{{ route('dashboard.population-records.index', ['view' => 'kk', 'hamlet' => $selectedHamlet !== 'Semua' ? $selectedHamlet : null, 'q' => $filters['q']]) }}">
                <span>Data KK</span><small>Kelola per keluarga</small>
            </a>
            <a class="population-tab {{ $viewMode === 'individual' ? 'active' : '' }}" href="{{ route('dashboard.population-records.index', ['view' => 'individual', 'hamlet' => $selectedHamlet !== 'Semua' ? $selectedHamlet : null, 'q' => $filters['q']]) }}">
                <span>Data Penduduk</span><small>Cari per individu</small>
            </a>
        </div>

        <form method="GET" action="{{ route('dashboard.population-records.index') }}" class="population-filter">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="field population-filter__search">
                <label for="q">Cari data</label>
                <input id="q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nama, NIK, atau nomor KK">
            </div>
            <div class="field">
                <label for="hamlet">Dusun</label>
                <select id="hamlet" name="hamlet">
                    <option value="">Semua Dusun</option>
                    @foreach($hamlets as $hamlet)
                        <option value="{{ $hamlet }}" @selected($selectedHamlet === $hamlet)>{{ $hamlet }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Terapkan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-records.index', ['view' => $viewMode]) }}">Reset</a>
            </div>
        </form>
    </section>

    @if($viewMode === 'kk')
        <section class="panel population-list-panel">
            <div class="toolbar">
                <div>
                    <h2>Daftar Kartu Keluarga</h2>
                    <p class="muted">Klik “Lihat KK” untuk membuka alamat dan anggota keluarga.</p>
                </div>
                <span class="result-count">{{ number_format($households->total(), 0, ',', '.') }} KK</span>
            </div>
            <div class="table-wrap population-table-wrap population-table-wrap--compact">
                <table class="responsive-data-table">
                    <thead>
                        <tr><th>No. KK</th><th>Kepala Keluarga</th><th>Wilayah</th><th>Alamat</th><th>Anggota</th><th>Diperbarui</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($households as $household)
                            <tr>
                                <td data-label="No. KK"><strong class="identifier">{{ $household->no_kk }}</strong></td>
                                <td data-label="Kepala Keluarga">{{ $household->nama_kepala_keluarga ?: 'Belum ditetapkan' }}</td>
                                <td data-label="Wilayah"><strong>{{ $household->dusun ?: '-' }}</strong><small class="table-subtext">RT {{ $household->rt ?: '-' }} / RW {{ $household->rw ?: '-' }}</small></td>
                                <td data-label="Alamat"><span class="line-clamp-2">{{ $household->alamat ?: '-' }}</span></td>
                                <td data-label="Anggota"><span class="member-count">{{ $household->total_members }} orang</span></td>
                                <td data-label="Diperbarui">{{ $household->updated_at?->format('d-m-Y') ?: '-' }}</td>
                                <td data-label="Aksi"><a class="btn btn-secondary" href="{{ route('dashboard.population-households.show', $household) }}">Lihat KK</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state"><strong>Belum ada KK yang sesuai</strong><span>Coba ubah pencarian atau tambahkan KK baru.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('dashboard.population.partials.pagination', ['paginator' => $households, 'label' => 'KK'])
        </section>
    @else
        <section class="panel population-list-panel">
            <div class="toolbar">
                <div><h2>Daftar Penduduk</h2><p class="muted">Data pokok ditampilkan ringkas; buka edit untuk biodata lengkap.</p></div>
                <span class="result-count">{{ number_format($items->total(), 0, ',', '.') }} penduduk</span>
            </div>
            <div class="table-wrap population-table-wrap population-table-wrap--compact">
                <table class="responsive-data-table">
                    <thead><tr><th>Nama / NIK</th><th>Jenis Kelamin</th><th>Tempat, Tanggal Lahir</th><th>KK / Kepala Keluarga</th><th>Hubungan</th><th>Wilayah</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td data-label="Nama / NIK"><strong>{{ $item->resolvedName() }}</strong><small class="table-subtext identifier">{{ $item->nik }}</small></td>
                                <td data-label="Jenis Kelamin">{{ $item->resolvedGender() }}</td>
                                <td data-label="TTL">{{ $item->resolvedBirthPlace() }}<small class="table-subtext">{{ $item->resolvedBirthDate()?->format('d-m-Y') ?: '-' }} · {{ $item->age !== null ? $item->age.' tahun' : '-' }}</small></td>
                                <td data-label="KK / Kepala"><span class="identifier">{{ $item->resolvedKkNumber() }}</span><small class="table-subtext">{{ $item->currentMembership?->household?->nama_kepala_keluarga ?: '-' }}</small></td>
                                <td data-label="Hubungan">{{ $item->resolvedStatusHubungan() }}</td>
                                <td data-label="Wilayah"><strong>{{ $item->resolvedHamlet() }}</strong><small class="table-subtext">RT {{ $item->resolvedRt() }} / RW {{ $item->resolvedRw() }}</small></td>
                                <td data-label="Aksi">
                                    <div class="actions">
                                        <a class="btn btn-secondary" href="{{ route('dashboard.population-records.edit', $item) }}">Edit</a>
                                        <form action="{{ route('dashboard.population-records.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus penduduk ini beserta riwayat keanggotaan KK?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state"><strong>Belum ada penduduk yang sesuai</strong><span>Coba ubah pencarian atau tambahkan penduduk.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('dashboard.population.partials.pagination', ['paginator' => $items, 'label' => 'penduduk'])
        </section>
    @endif

    <details
        class="panel population-analytics"
        data-analytics-panel
        data-statistics-url="{{ route('dashboard.population-records.statistics', ['hamlet' => $selectedHamlet !== 'Semua' ? $selectedHamlet : null, 'q' => $filters['q']]) }}"
    >
        <summary><span><strong>Lihat Statistik Kependudukan</strong><small>Distribusi dusun, jenis kelamin, usia, dan pendidikan mengikuti filter aktif.</small></span><span class="details-action">Tampilkan grafik</span></summary>
        <div class="chart-grid-dashboard population-chart-grid">
            <article class="chart-box"><h3>Penduduk per Dusun</h3><canvas id="populationHamletChart"></canvas></article>
            <article class="chart-box"><h3>Jenis Kelamin</h3><canvas id="populationGenderChart"></canvas></article>
            <article class="chart-box"><h3>Kelompok Usia</h3><canvas id="populationAgeChart"></canvas></article>
            <article class="chart-box"><h3>Pendidikan</h3><canvas id="populationEducationChart"></canvas></article>
        </div>
    </details>

    <script>
        (() => {
            const importPanel = document.getElementById('populationImportPanel');
            document.querySelector('[data-open-import]')?.addEventListener('click', () => {
                importPanel.open = true;
                importPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            document.querySelectorAll('[data-row-link]').forEach((row) => {
                const openRow = () => window.location.assign(row.dataset.rowLink);
                row.addEventListener('click', (event) => {
                    if (event.target.closest('a, button, input, select, textarea, form')) return;
                    openRow();
                });
                row.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    openRow();
                });
            });

            const form = document.getElementById('populationImportForm');
            const previewBox = document.querySelector('[data-import-preview]');
            const feedback = document.querySelector('[data-import-feedback]');
            const previewButton = document.querySelector('[data-preview-button]');
            const commitButton = document.querySelector('[data-commit-import]');
            const resetButton = document.querySelector('[data-reset-import]');
            const reportButton = document.querySelector('[data-download-report]');
            let previewToken = null;
            let previewData = null;

            const actionLabels = { new: 'Penduduk baru', update: 'Perbarui', unchanged: 'Tidak berubah', move: 'Pindah KK', invalid: 'Dilewati' };
            const escapeHtml = (value) => String(value ?? '-').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
            const setBusy = (button, busy, busyLabel, normalLabel) => {
                button.disabled = busy;
                button.textContent = busy ? busyLabel : normalLabel;
            };
            const showFeedback = (message, type = 'info') => {
                feedback.hidden = false;
                feedback.className = `import-feedback import-feedback--${type}`;
                feedback.textContent = message;
            };
            const responseMessage = async (response) => {
                const data = await response.json().catch(() => ({}));
                return { data, message: data.message || Object.values(data.errors || {}).flat()[0] || 'Permintaan gagal diproses.' };
            };

            const renderPreview = (payload, token) => {
                previewData = payload;
                previewToken = token;
                const summary = payload.summary;
                document.querySelector('[data-preview-meta]').textContent = `Sheet “${payload.sheet}”, header pada baris ${payload.header_row}.`;
                document.querySelector('[data-import-summary]').innerHTML = [
                    ['Total baris', summary.total], ['Valid', summary.valid], ['Bermasalah', summary.invalid],
                    ['KK baru', summary.households_created], ['Penduduk baru', summary.residents_created],
                    ['Diperbarui', summary.residents_updated], ['Tidak berubah', summary.residents_unchanged], ['Pindah KK', summary.residents_moved],
                ].map(([label, value]) => `<div><span>${label}</span><strong>${value}</strong></div>`).join('');

                document.querySelector('[data-preview-rows]').innerHTML = payload.rows.map((row) => {
                    const issues = row.issues.length
                        ? `<ul class="issue-list">${row.issues.map((issue) => `<li class="issue-${issue.severity}"><strong>${escapeHtml(issue.field)}</strong>: ${escapeHtml(issue.message)}${issue.hint ? `<small>${escapeHtml(issue.hint)}</small>` : ''}</li>`).join('')}</ul>`
                        : '<span class="preview-ok">Siap diimpor</span>';
                    return `<tr class="preview-row preview-row--${row.status}">
                        <td>${row.row}</td>
                        <td><strong>${escapeHtml(row.nama_lengkap)}</strong><small class="table-subtext identifier">${escapeHtml(row.nik)}</small></td>
                        <td class="identifier">${escapeHtml(row.no_kk)}</td>
                        <td><span class="status-pill status-pill--${row.status}">${actionLabels[row.status] || row.status}</span></td>
                        <td>${issues}</td>
                    </tr>`;
                }).join('');
                commitButton.disabled = summary.valid < 1;
                commitButton.textContent = summary.invalid > 0 ? `Import ${summary.valid} Baris Valid` : `Import ${summary.valid} Baris`;
                reportButton.hidden = !payload.rows.some((row) => row.issues.length);
                previewBox.hidden = false;
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!form.reportValidity()) return;
                setBusy(previewButton, true, 'Memeriksa…', 'Periksa File');
                previewBox.hidden = true;
                showFeedback('File sedang dibaca dan divalidasi. Jangan tutup halaman ini.');
                try {
                    const response = await fetch(form.dataset.previewUrl, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
                    const result = await responseMessage(response);
                    if (!response.ok) throw new Error(result.message);
                    renderPreview(result.data.preview, result.data.token);
                    showFeedback(result.message, result.data.preview.summary.invalid > 0 ? 'warning' : 'success');
                } catch (error) {
                    showFeedback(error.message, 'error');
                } finally {
                    setBusy(previewButton, false, 'Memeriksa…', 'Periksa File');
                }
            });

            commitButton?.addEventListener('click', async () => {
                if (!previewToken || !previewData || previewData.summary.valid < 1) return;
                setBusy(commitButton, true, 'Menyimpan…', 'Import Baris Valid');
                showFeedback('Menyimpan seluruh baris valid dalam satu transaksi.');
                const body = new FormData(form);
                body.append('preview_token', previewToken);
                try {
                    const response = await fetch(form.dataset.commitUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                    const result = await responseMessage(response);
                    if (response.status === 409 && result.data.preview) {
                        renderPreview(result.data.preview, result.data.token);
                        showFeedback(result.message, 'warning');
                        return;
                    }
                    if (!response.ok) throw new Error(result.message);
                    showFeedback(result.message, 'success');
                    window.setTimeout(() => window.location.assign(result.data.redirect), 700);
                } catch (error) {
                    showFeedback(error.message, 'error');
                } finally {
                    setBusy(commitButton, false, 'Menyimpan…', 'Import Baris Valid');
                }
            });

            resetButton?.addEventListener('click', () => {
                form.reset();
                previewToken = null;
                previewData = null;
                previewBox.hidden = true;
                feedback.hidden = true;
            });

            form?.querySelectorAll('input[type="file"], select').forEach((control) => {
                control.addEventListener('change', () => {
                    if (!previewToken) return;
                    previewToken = null;
                    previewData = null;
                    previewBox.hidden = true;
                    showFeedback('File atau pilihan dusun berubah. Jalankan pemeriksaan ulang.', 'warning');
                });
            });

            reportButton?.addEventListener('click', () => {
                if (!previewData) return;
                const rows = [['baris', 'nik', 'no_kk', 'nama_lengkap', 'status', 'kolom', 'tingkat', 'pesan', 'saran_perbaikan']];
                previewData.rows.forEach((row) => row.issues.forEach((issue) => rows.push([
                    row.row, row.nik, row.no_kk, row.nama_lengkap, row.status, issue.field, issue.severity, issue.message, issue.hint,
                ])));
                const csvCell = (value) => {
                    let text = String(value ?? '');
                    if (/^[=+\-@]/.test(text)) text = `'${text}`;
                    return `"${text.replaceAll('"', '""')}"`;
                };
                const blob = new Blob(['\uFEFF' + rows.map((row) => row.map(csvCell).join(';')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'laporan-validasi-kependudukan.csv';
                link.click();
                URL.revokeObjectURL(link.href);
            });

            const analyticsPanel = document.querySelector('[data-analytics-panel]');
            let chartsStarted = false;
            analyticsPanel?.addEventListener('toggle', async () => {
                if (!analyticsPanel.open || chartsStarted) return;
                chartsStarted = true;
                try {
                    const statisticsRequest = fetch(analyticsPanel.dataset.statisticsUrl, { headers: { Accept: 'application/json' } })
                        .then(async (response) => {
                            if (!response.ok) throw new Error('Data statistik tidak dapat dimuat.');
                            return response.json();
                        });
                    const chartRequest = new Promise((resolve, reject) => {
                        if (window.Chart) return resolve(window.Chart);
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        script.onload = () => resolve(window.Chart);
                        script.onerror = () => reject(new Error('Komponen grafik tidak dapat dimuat.'));
                        document.head.appendChild(script);
                    });
                    const [statistics] = await Promise.all([statisticsRequest, chartRequest]);
                    const common = { responsive: true, maintainAspectRatio: false };
                    new Chart(document.getElementById('populationHamletChart'), { type: 'bar', data: { labels: statistics.hamlets.labels, datasets: [{ label: 'Penduduk', data: statistics.hamlets.data, backgroundColor: '#0f4c81' }] }, options: common });
                    new Chart(document.getElementById('populationGenderChart'), { type: 'doughnut', data: { labels: statistics.genders.labels, datasets: [{ data: statistics.genders.data, backgroundColor: ['#2f80ed', '#d05f9a'] }] }, options: common });
                    new Chart(document.getElementById('populationAgeChart'), { type: 'bar', data: { labels: statistics.ages.labels, datasets: [{ label: 'Penduduk', data: statistics.ages.data, backgroundColor: '#1f8a70' }] }, options: common });
                    new Chart(document.getElementById('populationEducationChart'), { type: 'bar', data: { labels: statistics.education.labels, datasets: [{ label: 'Penduduk', data: statistics.education.data, backgroundColor: '#d08b2e' }] }, options: { ...common, indexAxis: 'y' } });
                } catch (error) {
                    chartsStarted = false;
                    showFeedback(`${error.message} Data tabel tetap dapat digunakan.`, 'warning');
                }
            });
        })();
    </script>
@endsection
