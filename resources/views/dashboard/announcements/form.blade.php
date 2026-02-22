@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    <section class="panel">
        <form method="POST" action="{{ $route }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <section class="ai-assist-card" data-ai-writer data-endpoint="{{ route('dashboard.ai.generate.announcement') }}" data-target-title="title" data-target-content="content">
                <div class="ai-assist-header">
                    <h3>Bantuan AI Penulisan Pengumuman</h3>
                    <p>AI hanya membantu menyusun draft. Tetap cek isi sebelum menyimpan.</p>
                </div>
                <div class="ai-assist-grid">
                    <div class="field">
                        <label for="ai_topic">Topik</label>
                        <input id="ai_topic" type="text" data-ai-topic placeholder="Contoh: Jadwal layanan administrasi pekan depan" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="ai_tone">Gaya Bahasa</label>
                        <select id="ai_tone" data-ai-tone>
                            <option value="informatif">Informatif</option>
                            <option value="formal">Formal</option>
                            <option value="netral">Netral</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="ai_length">Panjang Konten</label>
                        <select id="ai_length" data-ai-length>
                            <option value="short">Pendek</option>
                            <option value="medium">Sedang</option>
                            <option value="long">Panjang</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="ai_keywords">Kata Kunci (opsional)</label>
                        <input id="ai_keywords" type="text" data-ai-keywords placeholder="Contoh: jam pelayanan, syarat dokumen">
                    </div>
                    <div class="field full">
                        <label for="ai_brief">Informasi/Deskripsi Singkat</label>
                        <textarea id="ai_brief" data-ai-brief placeholder="Isi poin penting pengumuman: siapa, apa, kapan, dimana, syarat."></textarea>
                    </div>
                </div>
                <div class="actions ai-assist-actions">
                    <button class="btn btn-secondary" type="button" data-ai-generate>Generate Draft</button>
                    <span class="ai-assist-loading muted" data-ai-loading hidden>Sedang memproses...</span>
                </div>
                <div class="ai-assist-feedback" data-ai-feedback hidden></div>
            </section>

            <div class="form-grid">
                <div class="field full">
                    <label for="title">Judul</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $item->title) }}" required>
                </div>

                <div class="field full">
                    <label for="content">Isi Pengumuman</label>
                    <textarea id="content" name="content" required>{{ old('content', $item->content) }}</textarea>
                </div>

                <div class="field full">
                    <label for="link_url">URL Terkait (opsional)</label>
                    <input id="link_url" type="url" name="link_url" value="{{ old('link_url', $item->link_url) }}" placeholder="https://contoh.link/pengumuman">
                    <small class="muted">Isi jika ada tautan rujukan, formulir, atau dokumen tambahan.</small>
                </div>

                <div class="field">
                    <label for="start_date">Tanggal Mulai</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $item->start_date?->format('Y-m-d')) }}">
                    <small class="muted">Opsional. Kosongkan jika pengumuman tidak memakai periode khusus.</small>
                </div>

                <div class="field">
                    <label for="end_date">Tanggal Selesai</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $item->end_date?->format('Y-m-d')) }}">
                    <small class="muted">Opsional. Isi jika ada batas akhir.</small>
                </div>

                <div class="field full">
                    <label style="display:flex;align-items:center;gap:0.45rem;">
                        <input type="checkbox" name="is_active" value="1" style="width:auto;" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                        Pengumuman aktif
                    </label>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.announcements.index') }}">Kembali</a>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const writerCard = document.querySelector('[data-ai-writer]');
            if (!writerCard) {
                return;
            }

            const generateBtn = writerCard.querySelector('[data-ai-generate]');
            const feedback = writerCard.querySelector('[data-ai-feedback]');
            const loading = writerCard.querySelector('[data-ai-loading]');
            const endpoint = writerCard.dataset.endpoint;

            const topicInput = writerCard.querySelector('[data-ai-topic]');
            const briefInput = writerCard.querySelector('[data-ai-brief]');
            const toneInput = writerCard.querySelector('[data-ai-tone]');
            const lengthInput = writerCard.querySelector('[data-ai-length]');
            const keywordsInput = writerCard.querySelector('[data-ai-keywords]');

            const titleTarget = document.getElementById(writerCard.dataset.targetTitle);
            const contentTarget = document.getElementById(writerCard.dataset.targetContent);

            const setLoading = (isLoading) => {
                generateBtn.disabled = isLoading;
                loading.hidden = !isLoading;
            };

            const setFeedback = (message, type = 'error') => {
                feedback.hidden = false;
                feedback.classList.remove('is-error', 'is-success');
                feedback.classList.add(type === 'success' ? 'is-success' : 'is-error');
                feedback.textContent = message;
            };

            generateBtn.addEventListener('click', async () => {
                const topic = topicInput.value.trim();
                const brief = briefInput.value.trim();

                if (!topic || !brief) {
                    setFeedback('Topik dan informasi singkat wajib diisi sebelum generate.');
                    return;
                }

                setLoading(true);
                feedback.hidden = true;

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            topic,
                            brief,
                            tone: toneInput.value,
                            length: lengthInput.value,
                            keywords: keywordsInput.value.trim(),
                        }),
                    });

                    const json = await response.json();

                    if (!response.ok || !json.ok) {
                        let message = json.message || 'AI belum dapat memproses permintaan ini. Silakan lanjut manual.';
                        if (json.errors) {
                            const firstKey = Object.keys(json.errors)[0];
                            const firstError = json.errors[firstKey]?.[0];
                            if (typeof firstError === 'string') {
                                message = firstError;
                            }
                        }
                        setFeedback(message);
                        return;
                    }

                    if (titleTarget && json.data?.title) {
                        titleTarget.value = json.data.title;
                    }
                    if (contentTarget && json.data?.content) {
                        contentTarget.value = json.data.content;
                    }

                    setFeedback(json.message || 'Draft berhasil dibuat. Silakan review sebelum tayang.', 'success');
                } catch (error) {
                    setFeedback('Koneksi ke layanan AI terputus. Anda tetap bisa menulis manual.');
                } finally {
                    setLoading(false);
                }
            });
        })();
    </script>
@endsection
