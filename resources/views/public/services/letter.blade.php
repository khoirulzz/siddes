@extends('layouts.public')

@section('content')
    <div class="letter-online-page">
        <section class="letter-hero">
            <p class="letter-eyebrow">Layanan Digital Desa</p>
            <h1>Surat Online</h1>
            <p class="letter-lead">Isi data sekali, sistem membuat nomor surat, dokumen DOCX, dan file PDF yang bisa diunduh ulang kapan saja.</p>
            <div class="letter-flow">
                <span>1. Verifikasi NIK</span>
                <span>2. Isi Form Dinamis</span>
                <span>3. Submit & Dapat Nomor Surat</span>
                <span>4. Download PDF / DOCX</span>
            </div>
        </section>

        @if($errors->any())
            <div class="letter-alert letter-alert-danger">
                <strong>Data belum valid.</strong>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('error'))
            <div class="letter-alert letter-alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="letter-layout">
            <article class="letter-panel">
                <h2>Ajukan Surat Baru</h2>
                <p class="panel-copy">Mulai dari verifikasi NIK, lalu isi form sesuai jenis surat.</p>

                <div class="step-box">
                    <div class="step-title">
                        <span class="step-number">1</span>
                        Verifikasi NIK
                    </div>
                    <div class="step-content">
                        <label for="nikInput" class="form-label">NIK (16 digit)</label>
                        <div class="inline-field">
                            <input type="text" id="nikInput" class="form-control" maxlength="16" inputmode="numeric" placeholder="Contoh: 3326010101900001" value="{{ old('nik') }}">
                            <button class="btn btn-primary" type="button" id="btnCheckNik">Cek NIK</button>
                            <button class="btn btn-outline" type="button" id="btnResetNik">Reset</button>
                        </div>
                        <small class="text-muted">Data identitas akan terisi otomatis bila NIK terdaftar.</small>
                        <div id="nikFeedback" class="feedback-box"></div>
                    </div>
                </div>

                <form action="{{ route('services.letter.store') }}" method="POST" id="letterForm" class="{{ old('nik') ? '' : 'is-hidden' }}">
                    @csrf
                    <input type="hidden" name="nik" id="formNik" value="{{ old('nik') }}">
                    <input type="hidden" name="resident_name" id="residentNameHidden" value="{{ old('resident_name') }}">
                    <input type="hidden" name="resident_address" id="residentAddressHidden" value="{{ old('resident_address') }}">

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">2</span>
                            Data Pemohon
                        </div>
                        <div class="identity-card">
                            <div class="identity-item">
                                <small>Nama Lengkap</small>
                                <strong id="identityName">{{ old('resident_name', '-') }}</strong>
                            </div>
                            <div class="identity-item">
                                <small>Alamat</small>
                                <strong id="identityAddress">{{ old('resident_address', '-') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-title">
                            <span class="step-number">3</span>
                            Detail Surat
                        </div>
                        <div class="field-grid">
                            <div>
                                <label class="form-label" for="phoneInput">Nomor WhatsApp</label>
                                <input class="form-control" id="phoneInput" type="text" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
                            </div>
                            <div>
                                <label class="form-label" for="emailInput">Email (Opsional)</label>
                                <input class="form-control" id="emailInput" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com">
                            </div>
                        </div>

                        <div style="margin-top:0.9rem;">
                            <label class="form-label" for="letterType">Jenis Surat</label>
                            <select name="letter_type" id="letterType" class="form-select" required>
                                <option value="">Pilih jenis surat</option>
                                @foreach($letterTypes as $type)
                                    <option value="{{ $type }}" @selected(old('letter_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="dynamicFields" style="margin-top:0.9rem;"></div>
                    </div>

                    <div class="submit-row">
                        <button type="submit" class="btn btn-primary btn-lg" data-loading-text="Mengirim surat...">Kirim Pengajuan Surat</button>
                    </div>
                </form>
            </article>

            <aside class="letter-panel">
                <h2>Cari Nomor Surat</h2>
                <p class="panel-copy">Masukkan nomor tiket atau nomor surat resmi untuk cek status dan unduh ulang dokumen.</p>
                <label for="ticketInput" class="form-label">Nomor Surat</label>
                <div class="inline-field">
                    <input type="text" id="ticketInput" class="form-control" placeholder="Contoh: SRT-260216-ABCD / 001/SKU/LMBG/II/2026">
                    <button class="btn btn-outline" type="button" id="btnSearchTicket">Cari</button>
                </div>
                <small class="text-muted">Pencarian tidak peka huruf besar/kecil.</small>
                <div id="ticketSearchResult" class="feedback-box" style="margin-top:0.8rem;"></div>
            </aside>
        </div>
    </div>

    <style>
        .letter-online-page {
            padding: 1.2rem 0 2rem;
        }

        .letter-hero {
            border: 1px solid #bfe0ff;
            background: radial-gradient(circle at 8% 10%, #ffffff 0%, #edf6ff 40%, #e0eefc 100%);
            border-radius: 1rem;
            padding: 1.1rem 1.2rem;
            margin-bottom: 1rem;
        }

        .letter-eyebrow {
            margin: 0 0 0.4rem;
            color: #1f5c95;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .letter-hero h1 {
            margin: 0 0 0.4rem;
            font-size: 1.65rem;
        }

        .letter-lead {
            margin: 0;
            color: #355f84;
            max-width: 780px;
        }

        .letter-flow {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.5rem;
            margin-top: 0.9rem;
        }

        .letter-flow span {
            border: 1px dashed #93c5f9;
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
            color: #1f5c95;
            text-align: center;
            background: rgba(255, 255, 255, 0.7);
        }

        .letter-layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 0.9rem;
        }

        .letter-panel {
            border: 1px solid #d7e4f2;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 10px 28px rgba(17, 65, 109, 0.08);
            padding: 1rem;
        }

        .letter-panel h2 {
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

        .inline-field {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.45rem;
            align-items: center;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .identity-card {
            display: grid;
            gap: 0.6rem;
        }

        .identity-item {
            border: 1px solid #d4e2f1;
            border-radius: 0.65rem;
            padding: 0.65rem;
            background: #fff;
        }

        .identity-item small {
            display: block;
            color: #5f7590;
            font-size: 0.75rem;
            margin-bottom: 0.2rem;
        }

        .identity-item strong {
            font-size: 0.95rem;
            color: #0f3555;
        }

        .submit-row {
            margin-top: 0.7rem;
            display: flex;
            justify-content: flex-end;
        }

        .feedback-box {
            margin-top: 0.55rem;
        }

        .feedback-box .result-card {
            border: 1px solid #cfe2f7;
            border-radius: 0.7rem;
            background: #f7fbff;
            padding: 0.65rem;
            font-size: 0.88rem;
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

        .letter-alert {
            border-radius: 0.75rem;
            border: 1px solid #f2c6c6;
            background: #fff6f6;
            color: #8e2a2a;
            padding: 0.75rem 0.85rem;
            margin-bottom: 0.8rem;
            font-size: 0.88rem;
        }

        .letter-alert-danger strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .is-hidden {
            display: none;
        }

        @media (max-width: 980px) {
            .letter-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .inline-field {
                grid-template-columns: 1fr;
            }

            .field-grid {
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
            .letter-online-page {
                padding: 0.9rem 0 1.5rem;
            }

            .letter-hero,
            .letter-panel {
                padding: 0.85rem;
            }

            .letter-hero h1 {
                font-size: 1.34rem;
            }

            .letter-flow {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        (function () {
            const schema = @json($letterSchema);
            const oldDynamic = @json(old('dynamic_data', []));
            const oldLetterType = @json(old('letter_type'));
            const hasOldNik = @json((bool) old('nik'));
            const checkNikUrl = @json(route('services.api.check-nik'));
            const searchTicketUrl = @json(route('services.letter.search'));

            const nikInput = document.getElementById('nikInput');
            const btnCheckNik = document.getElementById('btnCheckNik');
            const btnResetNik = document.getElementById('btnResetNik');
            const nikFeedback = document.getElementById('nikFeedback');
            const letterForm = document.getElementById('letterForm');
            const formNik = document.getElementById('formNik');
            const residentNameHidden = document.getElementById('residentNameHidden');
            const residentAddressHidden = document.getElementById('residentAddressHidden');
            const identityName = document.getElementById('identityName');
            const identityAddress = document.getElementById('identityAddress');
            const letterType = document.getElementById('letterType');
            const dynamicFields = document.getElementById('dynamicFields');
            const ticketInput = document.getElementById('ticketInput');
            const btnSearchTicket = document.getElementById('btnSearchTicket');
            const ticketSearchResult = document.getElementById('ticketSearchResult');

            const escapeHtml = (value) => {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const showNikFeedback = (message, type = 'success') => {
                nikFeedback.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            const showSearchFeedback = (message, type = 'success') => {
                ticketSearchResult.innerHTML = `<div class="result-card ${type}">${message}</div>`;
            };

            const setIdentity = (name, address, nikValue) => {
                identityName.textContent = name || '-';
                identityAddress.textContent = address || '-';
                residentNameHidden.value = name || '';
                residentAddressHidden.value = address || '';
                formNik.value = nikValue || '';
            };

            const showForm = () => {
                letterForm.classList.remove('is-hidden');
            };

            const hideForm = () => {
                letterForm.classList.add('is-hidden');
            };

            const renderDynamicFields = (type, data = {}) => {
                dynamicFields.innerHTML = '';
                if (!type || !schema[type]) {
                    return;
                }

                const fields = schema[type].fields || [];
                if (!fields.length) {
                    dynamicFields.innerHTML = '<div class="result-card">Jenis surat ini tidak memiliki isian tambahan.</div>';
                    return;
                }

                const content = fields.map((field) => {
                    const value = data[field.name] ?? '';
                    const required = field.required ? 'required' : '';
                    const star = field.required ? '<span style="color:#c0392b">*</span>' : '';
                    const inputType = field.type || 'text';
                    const maxLength = field.max ?? 255;

                    let control = '';
                    if (inputType === 'select') {
                        const options = (field.options || []).map((option) => `
                            <option value="${escapeHtml(option)}" ${String(option) === String(value) ? 'selected' : ''}>${escapeHtml(option)}</option>
                        `).join('');
                        control = `
                            <select class="form-select" name="dynamic_data[${escapeHtml(field.name)}]" ${required}>
                                <option value="">Pilih</option>
                                ${options}
                            </select>
                        `;
                    } else if (inputType === 'date' || inputType === 'time') {
                        control = `
                            <input
                                class="form-control"
                                type="${escapeHtml(inputType)}"
                                name="dynamic_data[${escapeHtml(field.name)}]"
                                value="${escapeHtml(value)}"
                                ${required}
                            >
                        `;
                    } else {
                        control = `
                            <input
                                class="form-control"
                                type="text"
                                name="dynamic_data[${escapeHtml(field.name)}]"
                                maxlength="${escapeHtml(maxLength)}"
                                value="${escapeHtml(value)}"
                                placeholder="${escapeHtml(field.placeholder ?? '')}"
                                ${required}
                            >
                        `;
                    }

                    return `
                        <div style="margin-bottom:0.65rem;">
                            <label class="form-label">${escapeHtml(field.label)} ${star}</label>
                            ${control}
                        </div>
                    `;
                }).join('');

                dynamicFields.innerHTML = `
                    <div class="step-box" style="margin-top:0.8rem; margin-bottom:0;">
                        <div class="step-title" style="margin-bottom:0.45rem;">
                            <span class="step-number">4</span>
                            Isian Tambahan
                        </div>
                        ${content}
                    </div>
                `;
            };

            nikInput.addEventListener('input', () => {
                nikInput.value = nikInput.value.replace(/[^0-9]/g, '').slice(0, 16);
            });

            btnCheckNik.addEventListener('click', async () => {
                const nik = nikInput.value.trim();
                if (nik.length !== 16) {
                    showNikFeedback('NIK harus 16 digit angka.', 'error');
                    return;
                }

                showNikFeedback('Memeriksa data NIK...', 'loading');
                window.AppUi?.setButtonBusy(btnCheckNik, 'Mengecek...');

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

                    setIdentity(payload.full_name, payload.address_detail, payload.nik);
                    showForm();
                    showNikFeedback('NIK valid. Silakan lanjut isi detail surat.', 'success');
                } catch (error) {
                    showNikFeedback(error.message || 'Gagal memeriksa NIK.', 'error');
                    hideForm();
                } finally {
                    window.AppUi?.releaseButtonBusy(btnCheckNik);
                }
            });

            btnResetNik.addEventListener('click', () => {
                nikInput.value = '';
                setIdentity('-', '-', '');
                letterType.value = '';
                dynamicFields.innerHTML = '';
                nikFeedback.innerHTML = '';
                hideForm();
            });

            letterType.addEventListener('change', () => {
                renderDynamicFields(letterType.value, {});
            });

            btnSearchTicket.addEventListener('click', async () => {
                const ticket = ticketInput.value.trim();
                if (!ticket) {
                    showSearchFeedback('Nomor surat wajib diisi.', 'error');
                    return;
                }

                showSearchFeedback('Mencari nomor surat...', 'loading');
                window.AppUi?.setButtonBusy(btnSearchTicket, 'Mencari...');

                try {
                    const response = await fetch(`${searchTicketUrl}?q=${encodeURIComponent(ticket)}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Nomor surat tidak ditemukan.');
                    }

                    ticketSearchResult.innerHTML = `
                        <div class="result-card success">
                            <div><strong>Nomor Surat Resmi:</strong> ${escapeHtml(payload.official_number || '-')}
                                <button type="button" class="btn btn-outline btn-copy-inline" data-copy-value="${escapeHtml(payload.official_number || '')}" style="margin-left:0.35rem;">Salin</button>
                            </div>
                            <div><strong>Nomor Surat:</strong> ${escapeHtml(payload.ticket_number)}
                                <button type="button" class="btn btn-outline btn-copy-inline" data-copy-value="${escapeHtml(payload.ticket_number || '')}" style="margin-left:0.35rem;">Salin</button>
                            </div>
                            <div><strong>Status:</strong> ${escapeHtml(payload.status)}</div>
                            <div><strong>Jenis:</strong> ${escapeHtml(payload.letter_type)}</div>
                            <div><strong>Tanggal:</strong> ${escapeHtml(payload.submitted_at)}</div>
                            <div class="result-actions">
                                <a class="btn btn-primary" href="${escapeHtml(payload.download_url)}">Download PDF</a>
                                <a class="btn btn-outline" href="${escapeHtml(payload.download_docx_url)}">Download DOCX</a>
                            </div>
                        </div>
                    `;
                } catch (error) {
                    showSearchFeedback(error.message || 'Gagal mencari nomor surat.', 'error');
                } finally {
                    window.AppUi?.releaseButtonBusy(btnSearchTicket);
                }
            });

            if (hasOldNik) {
                showForm();
                setIdentity(residentNameHidden.value || '-', residentAddressHidden.value || '-', formNik.value || nikInput.value || '');
            }

            if (oldLetterType) {
                letterType.value = oldLetterType;
                renderDynamicFields(oldLetterType, oldDynamic || {});
            }
        })();
    </script>
@endsection
