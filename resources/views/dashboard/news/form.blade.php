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

            <section class="ai-assist-card" data-ai-writer data-endpoint="{{ route('dashboard.ai.generate.news') }}" data-target-title="title" data-target-excerpt="excerpt" data-target-content="content">
                <div class="ai-assist-header">
                    <h3>Bantuan AI Penulisan Berita</h3>
                    <p>Masukkan topik dan deskripsi singkat. Jika AI gagal, Anda tetap bisa lanjut manual.</p>
                </div>
                <div class="ai-assist-grid">
                    <div class="field">
                        <label for="ai_topic">Topik</label>
                        <input id="ai_topic" type="text" data-ai-topic placeholder="Contoh: Musyawarah rencana pembangunan desa" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="ai_tone">Gaya Bahasa</label>
                        <select id="ai_tone" data-ai-tone>
                            <option value="informatif">Informatif</option>
                            <option value="formal">Formal</option>
                            <option value="netral">Netral</option>
                            <option value="persuasif">Persuasif</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="ai_length">Panjang Konten</label>
                        <select id="ai_length" data-ai-length>
                            <option value="medium">Sedang</option>
                            <option value="short">Pendek</option>
                            <option value="long">Panjang</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="ai_keywords">Kata Kunci (opsional)</label>
                        <input id="ai_keywords" type="text" data-ai-keywords placeholder="Contoh: gotong royong, pembangunan jalan">
                    </div>
                    <div class="field full">
                        <label for="ai_brief">Informasi/Deskripsi Singkat</label>
                        <textarea id="ai_brief" data-ai-brief placeholder="Jelaskan inti kegiatan, waktu, lokasi, pihak yang terlibat, dan hasil utama."></textarea>
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
                    <label for="thumbnail">Thumbnail Berita</label>
                    <input id="thumbnail" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp">
                    <small class="muted">Unggah gambar 16:9. File otomatis dikompres agar hemat penyimpanan.</small>
                    @if($item->thumbnail_url)
                        <small class="muted">Thumbnail saat ini: <a href="{{ $item->thumbnail_url }}" target="_blank">Lihat gambar</a></small>
                    @endif
                    <div class="image-preview-frame{{ $item->thumbnail_url ? ' has-image' : '' }}" data-image-preview-frame>
                        <img src="{{ $item->thumbnail_url }}" alt="Preview thumbnail berita" data-image-preview {{ $item->thumbnail_url ? '' : 'hidden' }}>
                    </div>
                </div>

                <div class="field full">
                    <label for="excerpt">Ringkasan</label>
                    <textarea id="excerpt" name="excerpt">{{ old('excerpt', $item->excerpt) }}</textarea>
                </div>

                <div class="field full">
                    <label for="content">Konten</label>
                    <textarea id="content" name="content" required>{{ old('content', $item->content) }}</textarea>
                </div>

                <div class="field">
                    <label for="author_name">Penulis</label>
                    <input id="author_name" type="text" name="author_name" value="{{ old('author_name', $item->author_name) }}" required>
                </div>

                <div class="field">
                    <label for="published_at">Tanggal Publish</label>
                    <input id="published_at" type="datetime-local" name="published_at"
                           value="{{ old('published_at', $item->published_at ? $item->published_at->format('Y-m-d\\TH:i') : '') }}">
                </div>

                <div class="field full">
                    <label style="display:flex;align-items:center;gap:0.45rem;">
                        <input type="checkbox" name="is_published" value="1" style="width:auto;" {{ old('is_published', $item->is_published) ? 'checked' : '' }}>
                        Publish berita
                    </label>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.news.index') }}">Kembali</a>
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
            const excerptTarget = document.getElementById(writerCard.dataset.targetExcerpt);
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
                        let message = json.message || 'Terjadi kendala saat memproses AI. Anda tetap bisa menulis manual.';
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
                    if (excerptTarget && json.data?.excerpt) {
                        excerptTarget.value = json.data.excerpt;
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

        (() => {
            const input = document.getElementById('thumbnail');
            const previewFrame = document.querySelector('[data-image-preview-frame]');
            const previewImage = document.querySelector('[data-image-preview]');

            if (!input || !previewFrame || !previewImage) {
                return;
            }

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                const objectUrl = URL.createObjectURL(file);
                previewImage.src = objectUrl;
                previewImage.hidden = false;
                previewFrame.classList.add('has-image');
            });
        })();
    </script>
@endsection
