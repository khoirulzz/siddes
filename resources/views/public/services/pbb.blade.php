@extends('layouts.public')

@section('title', 'Layanan PBB - ' . config('village.name'))

@section('content')
    <div class="service-page">
        <section class="service-hero">
            <p class="service-eyebrow">Layanan Digital Desa</p>
            <h1>Permohonan Layanan PBB</h1>
            <p class="service-lead">
                Isi nama dan nomor WhatsApp aktif, lalu cari NOP yang akan diajukan.
                Satu permohonan bisa berisi beberapa NOP sekaligus.
            </p>
            <div class="service-flow">
                <span>1. Isi Identitas Pemohon</span>
                <span>2. Cari & Konfirmasi NOP</span>
                <span>3. Kirim Permohonan</span>
                <span>4. Simpan Nomor Tiket</span>
            </div>
        </section>

        @if(session('success'))
            <div class="service-alert service-alert-success">{{ session('success') }}</div>
        @endif

        @if(session('pbb_ticket'))
            <div class="service-ticket-card">
                <div>
                    <small>Nomor tiket PBB</small>
                    <strong>{{ session('pbb_ticket') }}</strong>
                </div>
                <button
                    type="button"
                    class="btn btn-outline btn-copy-inline"
                    data-copy-value="{{ session('pbb_ticket') }}"
                >
                    Salin tiket
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="service-alert service-alert-danger">
                <strong>Data belum valid.</strong>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="service-layout">
            <article class="service-panel">
                <h2>Ajukan Permohonan PBB</h2>
                <p class="panel-copy">Data pemohon cukup Nama dan Nomor WhatsApp aktif.</p>

                <form action="{{ route('services.pbb.store') }}" method="POST" id="pbbForm">
                    @csrf

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">1</span>
                            Data Pemohon
                        </div>
                        <div class="field-grid">
                            <div>
                                <label class="form-label" for="applicantName">Nama Pemohon</label>
                                <input
                                    id="applicantName"
                                    class="form-control"
                                    type="text"
                                    name="applicant_name"
                                    value="{{ old('applicant_name') }}"
                                    placeholder="Contoh: Sari Puspita"
                                    required
                                >
                            </div>
                            <div>
                                <label class="form-label" for="phoneInput">No. WhatsApp Aktif</label>
                                <input
                                    id="phoneInput"
                                    class="form-control"
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="08xxxxxxxxxx"
                                    inputmode="tel"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">2</span>
                            Cari NOP
                        </div>
                        <div class="inline-field">
                            <input
                                type="text"
                                id="nopInput"
                                class="form-control"
                                placeholder="Masukkan NOP, contoh: 33.26.010.001.001"
                                autocomplete="off"
                            >
                            <button class="btn btn-primary" type="button" id="btnSearchNop">Cari NOP</button>
                        </div>
                        <small class="text-muted">NOP dicocokkan otomatis walau penulisan menggunakan titik atau tanpa titik.</small>
                        <div id="nopFeedback" class="feedback-box"></div>
                    </div>

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">3</span>
                            Daftar NOP Diajukan
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>NOP</th>
                                        <th>Nama Objek Pajak</th>
                                        <th>Tahun</th>
                                        <th>Nominal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="nopTableBody">
                                    <tr id="nopEmptyRow">
                                        <td colspan="5" style="text-align:center; color:#5f7590;">Belum ada NOP ditambahkan.</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"><strong>Total NOP</strong></td>
                                        <td colspan="2"><strong id="totalNopText">0</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"><strong>Total Nominal</strong></td>
                                        <td colspan="2"><strong id="totalAmountText">Rp 0</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="nopsHiddenContainer"></div>
                    </div>

                    <div class="submit-row">
                        <button type="submit" class="btn btn-primary btn-lg">Kirim Permohonan PBB</button>
                    </div>
                </form>
            </article>

            <aside class="service-panel">
                <h2>Cek Status Tiket PBB</h2>
                <p class="panel-copy">Masukkan nomor tiket untuk memantau status permohonan.</p>
                <label class="form-label" for="ticketInput">Nomor Tiket</label>
                <div class="inline-field">
                    <input
                        type="text"
                        id="ticketInput"
                        class="form-control"
                        placeholder="Contoh: PBB-260218-ABCD"
                        autocomplete="off"
                    >
                    <button class="btn btn-outline" type="button" id="btnSearchTicket">Cari</button>
                </div>
                <div id="ticketFeedback" class="feedback-box" style="margin-top:0.7rem;"></div>
            </aside>
        </div>
    </div>

    <style>
        .service-page {
            padding: 1.2rem 0 2rem;
        }

        .service-hero {
            border: 1px solid #bfe0ff;
            background: radial-gradient(circle at 8% 10%, #ffffff 0%, #edf6ff 40%, #e0eefc 100%);
            border-radius: 1rem;
            padding: 1.1rem 1.2rem;
            margin-bottom: 1rem;
        }

        .service-eyebrow {
            margin: 0 0 0.4rem;
            color: #1f5c95;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .service-hero h1 {
            margin: 0 0 0.4rem;
            font-size: 1.65rem;
        }

        .service-lead {
            margin: 0;
            color: #355f84;
            max-width: 800px;
        }

        .service-flow {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.5rem;
            margin-top: 0.9rem;
        }

        .service-flow span {
            border: 1px dashed #93c5f9;
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
            color: #1f5c95;
            text-align: center;
            background: rgba(255, 255, 255, 0.7);
        }

        .service-layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 0.9rem;
            min-width: 0;
        }

        .service-panel {
            border: 1px solid #d7e4f2;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 10px 28px rgba(17, 65, 109, 0.08);
            padding: 1rem;
            min-width: 0;
        }

        .service-panel h2 {
            margin: 0;
            font-size: 1.08rem;
        }

        .panel-copy {
            margin: 0.35rem 0 0.9rem;
            color: #4f6780;
            font-size: 0.9rem;
        }

        .step-box {
            border: 1px solid #dbe9f7;
            border-radius: 0.8rem;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            background: linear-gradient(180deg, #fafcff 0%, #f5f9ff 100%);
        }

        .step-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: #123f68;
            margin-bottom: 0.55rem;
        }

        .step-number {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0f4c81;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .form-label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #163d5f;
            margin-bottom: 0.35rem;
        }

        .form-control {
            width: 100%;
            border: 1px solid #bfd4e8;
            border-radius: 0.6rem;
            padding: 0.55rem 0.65rem;
            font-size: 0.9rem;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #6fa7d8;
            box-shadow: 0 0 0 3px rgba(72, 131, 185, 0.16);
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .inline-field {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.45rem;
            align-items: center;
        }

        .text-muted {
            color: #637d97;
            font-size: 0.8rem;
        }

        .feedback-box .result-card {
            border: 1px solid #cfe2f7;
            border-radius: 0.7rem;
            background: #f7fbff;
            padding: 0.65rem;
            font-size: 0.88rem;
            margin-top: 0.55rem;
        }

        .feedback-box .result-card.error {
            border-color: #f0c9c9;
            background: #fff6f6;
            color: #9c2f2f;
        }

        .feedback-box .result-card.success {
            border-color: #bfe6cf;
            background: #f5fff9;
            color: #176b3d;
        }

        .feedback-box .result-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.45rem;
            flex-wrap: wrap;
        }

        .submit-row {
            margin-top: 0.7rem;
            display: flex;
            justify-content: flex-end;
        }

        .service-alert {
            border-radius: 0.75rem;
            padding: 0.75rem 0.85rem;
            margin-bottom: 0.8rem;
            font-size: 0.88rem;
            border: 1px solid transparent;
        }

        .service-alert-success {
            border-color: #bfe6cf;
            background: #f5fff9;
            color: #176b3d;
        }

        .service-alert-danger {
            border-color: #f2c6c6;
            background: #fff6f6;
            color: #8e2a2a;
        }

        .service-alert-danger strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .service-ticket-card {
            border: 1px dashed #94bce0;
            border-radius: 0.82rem;
            background: #eef6ff;
            padding: 0.75rem 0.82rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            flex-wrap: wrap;
        }

        .service-ticket-card small {
            display: block;
            color: #4c6f8f;
            font-size: 0.78rem;
            margin-bottom: 0.15rem;
        }

        .service-ticket-card strong {
            color: #0f4c81;
            font-size: 1.04rem;
            letter-spacing: 0.02em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d4e2f1;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #fff;
        }

        th,
        td {
            border-bottom: 1px solid #dbe8f5;
            padding: 0.5rem;
            font-size: 0.82rem;
            text-align: left;
            vertical-align: top;
        }

        th {
            white-space: nowrap;
            background: #f5f9ff;
            color: #1b4e78;
        }

        tfoot td {
            background: #fafcff;
        }

        .table-wrap {
            overflow-x: auto;
            max-width: 100%;
            width: 100%;
        }

        .table-wrap table {
            min-width: 620px;
        }

        @media (max-width: 980px) {
            .service-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .field-grid {
                grid-template-columns: 1fr;
            }

            .inline-field {
                grid-template-columns: 1fr;
            }

            .submit-row {
                justify-content: stretch;
            }

            .submit-row .btn {
                width: 100%;
            }

            .table-wrap table {
                min-width: 520px;
            }

            th {
                white-space: normal;
            }
        }

        @media (max-width: 520px) {
            .service-page {
                padding: 0.9rem 0 1.5rem;
            }

            .service-hero,
            .service-panel {
                padding: 0.85rem;
            }

            .service-hero h1 {
                font-size: 1.34rem;
            }

            .service-flow {
                grid-template-columns: 1fr;
            }

            .service-ticket-card strong {
                font-size: 0.95rem;
                word-break: break-word;
            }
        }
    </style>

    <script>
        (function () {
            const searchNopUrl = @json(route('services.api.search-nop'));
            const searchTicketUrl = @json(route('services.pbb.search'));
            const oldNops = @json(old('nops', []));

            const nopInput = document.getElementById('nopInput');
            const btnSearchNop = document.getElementById('btnSearchNop');
            const nopFeedback = document.getElementById('nopFeedback');
            const nopTableBody = document.getElementById('nopTableBody');
            const nopEmptyRow = document.getElementById('nopEmptyRow');
            const nopsHiddenContainer = document.getElementById('nopsHiddenContainer');
            const totalNopText = document.getElementById('totalNopText');
            const totalAmountText = document.getElementById('totalAmountText');
            const pbbForm = document.getElementById('pbbForm');

            const ticketInput = document.getElementById('ticketInput');
            const btnSearchTicket = document.getElementById('btnSearchTicket');
            const ticketFeedback = document.getElementById('ticketFeedback');

            let currentSearchResult = null;
            const selectedNops = [];

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const formatRupiah = (amount) => {
                return new Intl.NumberFormat('id-ID').format(Number(amount || 0));
            };

            const showNopMessage = (message, type = 'success') => {
                nopFeedback.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            const showTicketMessage = (message, type = 'success') => {
                ticketFeedback.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            const isDuplicate = (item) => {
                return selectedNops.some((existing) => {
                    return existing.nop === item.nop && Number(existing.tax_year) === Number(item.tax_year);
                });
            };

            const renderNops = () => {
                nopTableBody.innerHTML = '';
                nopsHiddenContainer.innerHTML = '';

                if (!selectedNops.length) {
                    nopTableBody.appendChild(nopEmptyRow);
                } else {
                    selectedNops.forEach((item, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${escapeHtml(item.nop)}</td>
                            <td>
                                <strong>${escapeHtml(item.tax_name || '-')}</strong>
                                <div style="color:#5f7590; font-size:0.75rem; margin-top:0.2rem;">${escapeHtml(item.address || '-')}</div>
                            </td>
                            <td>${escapeHtml(item.tax_year)}</td>
                            <td>Rp ${escapeHtml(formatRupiah(item.amount_due))}</td>
                            <td><button class="btn btn-danger" type="button" data-remove-index="${index}">Hapus</button></td>
                        `;
                        nopTableBody.appendChild(row);

                        const nopInputHidden = document.createElement('input');
                        nopInputHidden.type = 'hidden';
                        nopInputHidden.name = `nops[${index}][nop]`;
                        nopInputHidden.value = item.nop;
                        nopsHiddenContainer.appendChild(nopInputHidden);

                        const yearInputHidden = document.createElement('input');
                        yearInputHidden.type = 'hidden';
                        yearInputHidden.name = `nops[${index}][tax_year]`;
                        yearInputHidden.value = item.tax_year;
                        nopsHiddenContainer.appendChild(yearInputHidden);
                    });
                }

                const totalNominal = selectedNops.reduce((carry, item) => carry + Number(item.amount_due || 0), 0);
                totalNopText.textContent = String(selectedNops.length);
                totalAmountText.textContent = `Rp ${formatRupiah(totalNominal)}`;
            };

            const addCurrentSearchResult = () => {
                if (!currentSearchResult) {
                    return;
                }

                if (isDuplicate(currentSearchResult)) {
                    showNopMessage('NOP tersebut sudah ada dalam daftar.', 'error');
                    return;
                }

                selectedNops.push(currentSearchResult);
                currentSearchResult = null;
                nopInput.value = '';
                showNopMessage('NOP berhasil ditambahkan ke daftar pengajuan.', 'success');
                renderNops();
            };

            const hydrateOldNops = () => {
                if (!Array.isArray(oldNops) || oldNops.length === 0) {
                    return;
                }

                oldNops.forEach((item) => {
                    const nop = String(item?.nop || '').trim();
                    if (!nop) {
                        return;
                    }

                    selectedNops.push({
                        nop,
                        tax_name: item?.tax_name || '(data lama)',
                        address: item?.address || '-',
                        tax_year: Number(item?.tax_year || new Date().getFullYear()),
                        amount_due: Number(item?.amount_due || 0),
                    });
                });

                renderNops();
            };

            btnSearchNop.addEventListener('click', async () => {
                const nop = nopInput.value.trim();
                if (!nop) {
                    showNopMessage('NOP wajib diisi sebelum pencarian.', 'error');
                    return;
                }

                showNopMessage('Mencari data NOP...');

                try {
                    const response = await fetch(`${searchNopUrl}?nop=${encodeURIComponent(nop)}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'NOP tidak ditemukan.');
                    }

                    currentSearchResult = {
                        nop: payload.nop,
                        tax_name: payload.tax_name,
                        address: payload.address,
                        tax_year: payload.tax_year,
                        amount_due: Number(payload.amount_due || 0),
                    };

                    showNopMessage(`
                        <div><strong>${escapeHtml(currentSearchResult.tax_name || '-')}</strong></div>
                        <div style="margin-top:0.2rem;">NOP: ${escapeHtml(currentSearchResult.nop)}</div>
                        <div>Tahun: ${escapeHtml(currentSearchResult.tax_year)}</div>
                        <div>Nominal: Rp ${escapeHtml(formatRupiah(currentSearchResult.amount_due))}</div>
                        <div style="margin-top:0.2rem;">Alamat: ${escapeHtml(currentSearchResult.address || '-')}</div>
                        <div class="result-actions">
                            <button type="button" class="btn btn-primary" id="btnConfirmAddNop">Tambahkan NOP</button>
                        </div>
                    `, 'success');

                    document.getElementById('btnConfirmAddNop')?.addEventListener('click', addCurrentSearchResult, { once: true });
                } catch (error) {
                    currentSearchResult = null;
                    showNopMessage(error.message || 'Gagal mencari data NOP.', 'error');
                }
            });

            nopTableBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const index = target.getAttribute('data-remove-index');
                if (index === null) {
                    return;
                }

                selectedNops.splice(Number(index), 1);
                renderNops();
            });

            pbbForm.addEventListener('submit', (event) => {
                if (selectedNops.length < 1) {
                    event.preventDefault();
                    showNopMessage('Tambahkan minimal satu NOP sebelum mengirim permohonan.', 'error');
                }
            });

            btnSearchTicket.addEventListener('click', async () => {
                const ticket = ticketInput.value.trim();
                if (!ticket) {
                    showTicketMessage('Nomor tiket wajib diisi.', 'error');
                    return;
                }

                showTicketMessage('Mencari tiket...');

                try {
                    const response = await fetch(`${searchTicketUrl}?q=${encodeURIComponent(ticket)}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Nomor tiket tidak ditemukan.');
                    }

                    showTicketMessage(`
                        <div><strong>Nomor Tiket:</strong> ${escapeHtml(payload.ticket_code || '-')}
                            <button type="button" class="btn btn-outline btn-copy-inline" data-copy-value="${escapeHtml(payload.ticket_code || '')}" style="margin-left:0.35rem;">Salin</button>
                        </div>
                        <div><strong>Pemohon:</strong> ${escapeHtml(payload.applicant_name || '-')}</div>
                        <div><strong>Status:</strong> ${escapeHtml(payload.status || '-')}</div>
                        <div><strong>Total NOP:</strong> ${escapeHtml(payload.total_nop || 0)}</div>
                        <div><strong>Total Nominal:</strong> Rp ${escapeHtml(formatRupiah(payload.total_amount || 0))}</div>
                        <div><strong>Tanggal:</strong> ${escapeHtml(payload.submitted_at || '-')}</div>
                    `, 'success');
                } catch (error) {
                    showTicketMessage(error.message || 'Gagal mencari tiket.', 'error');
                }
            });

            hydrateOldNops();
            renderNops();
        })();
    </script>
@endsection
