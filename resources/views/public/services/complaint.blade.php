@extends('layouts.public')

@section('title', 'Pengaduan Masyarakat - ' . config('village.name'))

@section('content')
    <div class="service-page">
        <section class="service-hero">
            <p class="service-eyebrow">Layanan Digital Desa</p>
            <h1>Pengaduan Masyarakat</h1>
            <p class="service-lead">
                Pengaduan hanya dapat diajukan oleh warga terdaftar. Verifikasi NIK terlebih dahulu, lalu isi detail laporan dengan jelas.
            </p>
            <div class="service-flow">
                <span>1. Verifikasi NIK</span>
                <span>2. Isi Data Pengaduan</span>
                <span>3. Kirim & Simpan Tiket</span>
            </div>
        </section>

        @if(session('success'))
            <div class="service-alert service-alert-success">{{ session('success') }}</div>
        @endif

        @if(session('complaint_ticket'))
            <div class="service-ticket-card">
                <div>
                    <small>Nomor tiket pengaduan</small>
                    <strong>{{ session('complaint_ticket') }}</strong>
                </div>
                <button
                    type="button"
                    class="btn btn-outline btn-copy-inline"
                    data-copy-value="{{ session('complaint_ticket') }}"
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

        <div class="service-layout single-column">
            <article class="service-panel">
                <h2>Kirim Pengaduan</h2>
                <p class="panel-copy">
                    Tuliskan kronologi singkat, lokasi kejadian, dan lampiran pendukung jika ada.
                </p>

                <form method="POST" action="{{ route('services.complaint.store') }}" enctype="multipart/form-data" id="complaintForm">
                    @csrf

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">1</span>
                            Verifikasi NIK
                        </div>
                        <div class="inline-field">
                            <input
                                type="text"
                                id="nikInput"
                                name="nik"
                                class="form-control"
                                value="{{ old('nik') }}"
                                placeholder="Masukkan NIK 16 digit"
                                inputmode="numeric"
                                maxlength="16"
                                required
                            >
                            <button class="btn btn-primary" type="button" id="btnCheckNik">Cek NIK</button>
                        </div>
                        <small class="text-muted">NIK harus terdaftar pada data kependudukan desa.</small>
                        <div id="nikFeedback" class="feedback-box"></div>
                    </div>

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">2</span>
                            Data Pelapor
                        </div>
                        <div class="field-grid">
                            <div>
                                <label class="form-label" for="reporterName">Nama Pelapor</label>
                                <input
                                    id="reporterName"
                                    type="text"
                                    name="reporter_name"
                                    value="{{ old('reporter_name') }}"
                                    class="form-control"
                                    placeholder="Nama sesuai identitas"
                                    required
                                >
                            </div>
                            <div>
                                <label class="form-label" for="phoneInput">No. HP / WhatsApp</label>
                                <input
                                    id="phoneInput"
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    class="form-control"
                                    placeholder="08xxxxxxxxxx"
                                    required
                                >
                            </div>
                        </div>
                        <div style="margin-top:0.65rem;">
                            <label class="form-label" for="emailInput">Email (Opsional)</label>
                            <input
                                id="emailInput"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control"
                                placeholder="nama@email.com"
                            >
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">3</span>
                            Detail Pengaduan
                        </div>
                        <div class="field-grid">
                            <div>
                                <label class="form-label" for="subjectInput">Judul Pengaduan</label>
                                <input
                                    id="subjectInput"
                                    type="text"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    class="form-control"
                                    placeholder="Contoh: Lampu jalan mati"
                                    required
                                >
                            </div>
                            <div>
                                <label class="form-label" for="categoryInput">Kategori</label>
                                <select id="categoryInput" name="category" class="form-control" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="Infrastruktur" @selected(old('category') === 'Infrastruktur')>Infrastruktur</option>
                                    <option value="Pelayanan Publik" @selected(old('category') === 'Pelayanan Publik')>Pelayanan Publik</option>
                                    <option value="Sosial" @selected(old('category') === 'Sosial')>Sosial</option>
                                    <option value="Keamanan" @selected(old('category') === 'Keamanan')>Keamanan</option>
                                    <option value="Lainnya" @selected(old('category') === 'Lainnya')>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:0.65rem;">
                            <label class="form-label" for="locationInput">Lokasi Kejadian (Opsional)</label>
                            <input
                                id="locationInput"
                                type="text"
                                name="location"
                                value="{{ old('location') }}"
                                class="form-control"
                                placeholder="Contoh: RT 04 RW 02 Dusun Sasak"
                            >
                        </div>
                        <div style="margin-top:0.65rem;">
                            <label class="form-label" for="descriptionInput">Uraian Pengaduan</label>
                            <textarea
                                id="descriptionInput"
                                name="description"
                                class="form-control"
                                rows="5"
                                placeholder="Jelaskan kejadian secara ringkas dan jelas."
                                required
                            >{{ old('description') }}</textarea>
                        </div>
                        <div style="margin-top:0.65rem;">
                            <label class="form-label" for="evidenceInput">Lampiran Bukti (Opsional)</label>
                            <input
                                id="evidenceInput"
                                type="file"
                                name="evidence"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.mov"
                            >
                            <small class="text-muted">Format: JPG, PNG, WEBP, PDF, MP4, MOV. Maksimal 5 MB.</small>
                        </div>
                    </div>

                    <div class="submit-row">
                        <button class="btn btn-primary btn-lg" type="submit">Kirim Pengaduan</button>
                    </div>
                </form>
            </article>

            <article class="service-panel">
                <h2>Cari Status Tiket Pengaduan</h2>
                <p class="panel-copy">Masukkan nomor tiket untuk melihat status penanganan pengaduan.</p>
                <div class="inline-field">
                    <input
                        type="text"
                        id="complaintTicketInput"
                        class="form-control"
                        placeholder="Contoh: PGD-260220-ABCD"
                        autocomplete="off"
                    >
                    <button class="btn btn-outline" type="button" id="btnSearchComplaintTicket">Cari</button>
                </div>
                <div id="complaintTicketFeedback" class="feedback-box"></div>
            </article>
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
            max-width: 820px;
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
            gap: 0.9rem;
        }

        .single-column {
            grid-template-columns: 1fr;
        }

        .service-panel {
            border: 1px solid #d7e4f2;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 10px 28px rgba(17, 65, 109, 0.08);
            padding: 1rem;
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

        @media (max-width: 780px) {
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
            const checkNikUrl = @json(route('services.api.check-nik'));
            const searchComplaintTicketUrl = @json(route('services.complaint.search'));
            const nikInput = document.getElementById('nikInput');
            const btnCheckNik = document.getElementById('btnCheckNik');
            const nikFeedback = document.getElementById('nikFeedback');
            const reporterName = document.getElementById('reporterName');
            const complaintTicketInput = document.getElementById('complaintTicketInput');
            const btnSearchComplaintTicket = document.getElementById('btnSearchComplaintTicket');
            const complaintTicketFeedback = document.getElementById('complaintTicketFeedback');

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const showNikMessage = (message, type = 'success') => {
                nikFeedback.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            const showTicketMessage = (message, type = 'success') => {
                complaintTicketFeedback.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            nikInput.addEventListener('input', () => {
                nikInput.value = nikInput.value.replace(/[^0-9]/g, '').slice(0, 16);
            });

            btnCheckNik.addEventListener('click', async () => {
                const nik = nikInput.value.trim();
                if (nik.length !== 16) {
                    showNikMessage('NIK harus 16 digit angka.', 'error');
                    return;
                }

                showNikMessage('Memeriksa data NIK...');

                try {
                    const response = await fetch(`${checkNikUrl}?nik=${encodeURIComponent(nik)}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'NIK tidak ditemukan.');
                    }

                    if (!reporterName.value.trim()) {
                        reporterName.value = payload.full_name || '';
                    }

                    showNikMessage(`
                        <div><strong>NIK valid.</strong></div>
                        <div>Nama: ${escapeHtml(payload.full_name || '-')}</div>
                        <div>Alamat: ${escapeHtml(payload.address_detail || '-')}</div>
                    `, 'success');
                } catch (error) {
                    showNikMessage(error.message || 'Gagal memeriksa NIK.', 'error');
                }
            });

            btnSearchComplaintTicket?.addEventListener('click', async () => {
                const ticket = complaintTicketInput.value.trim();
                if (!ticket) {
                    showTicketMessage('Nomor tiket wajib diisi.', 'error');
                    return;
                }

                showTicketMessage('Mencari tiket pengaduan...');

                try {
                    const response = await fetch(`${searchComplaintTicketUrl}?q=${encodeURIComponent(ticket)}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Nomor tiket pengaduan tidak ditemukan.');
                    }

                    const responseNote = payload.response
                        ? `<div><strong>Tanggapan Admin:</strong> ${escapeHtml(payload.response)}</div>`
                        : '<div><strong>Tanggapan Admin:</strong> Belum ada tanggapan.</div>';
                    const evidenceNote = payload.evidence_url
                        ? `<div><a class="btn btn-outline" href="${escapeHtml(payload.evidence_url)}" target="_blank" rel="noopener">Lihat Lampiran</a></div>`
                        : '';

                    showTicketMessage(`
                        <div><strong>Nomor Tiket:</strong> ${escapeHtml(payload.ticket_code || '-')}
                            <button type="button" class="btn btn-outline btn-copy-inline" data-copy-value="${escapeHtml(payload.ticket_code || '')}" style="margin-left:0.35rem;">Salin</button>
                        </div>
                        <div><strong>Pelapor:</strong> ${escapeHtml(payload.reporter_name || '-')}</div>
                        <div><strong>Judul:</strong> ${escapeHtml(payload.subject || '-')}</div>
                        <div><strong>Kategori:</strong> ${escapeHtml(payload.category || '-')}</div>
                        <div><strong>Status:</strong> ${escapeHtml(payload.status || '-')}</div>
                        <div><strong>Tanggal:</strong> ${escapeHtml(payload.submitted_at || '-')}</div>
                        ${responseNote}
                        ${evidenceNote}
                    `, 'success');
                } catch (error) {
                    showTicketMessage(error.message || 'Gagal mencari tiket pengaduan.', 'error');
                }
            });
        })();
    </script>
@endsection
