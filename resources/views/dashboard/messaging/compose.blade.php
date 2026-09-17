@extends('dashboard.messaging.layout')
@section('messaging-content')
<section class="panel">
    <div class="toolbar"><div><h2>Buat campaign</h2><p>Siapkan pesan, pilih penerima, lalu tinjau sebelum membuat draft.</p></div><a class="btn btn-secondary" href="{{ route('dashboard.messaging.campaigns') }}">Kembali</a></div>
    <form class="messaging-form" method="POST" action="{{ route('dashboard.messaging.campaigns.store') }}" data-compose data-recipients-url="{{ route('dashboard.messaging.recipients') }}" data-preview-url="{{ route('dashboard.messaging.campaigns.preview') }}" data-selected="{{ json_encode(old('contactIds', [])) }}">
        @csrf
        <input type="hidden" name="request_uuid" value="{{ $requestUuid }}">
        <input type="hidden" name="previewToken" value="">
        <input type="hidden" name="templateId" value="{{ old('templateId') }}">
        <div class="messaging-compose-grid">
            <div class="messaging-compose-main">
                <label>Nama campaign<input name="name" required minlength="2" maxlength="120" value="{{ old('name') }}" placeholder="Contoh: Pengumuman layanan desa"></label>
                <details class="messaging-template-picker"><summary>Pilih template pesan</summary><div data-template-list></div><button type="button" class="btn btn-secondary" data-template-more hidden>Template berikutnya</button></details>
                <label>Isi pesan<textarea name="content" rows="9" required maxlength="4000" data-message-input>{{ old('content') }}</textarea></label>
                <div class="messaging-section-heading"><small class="muted">Personalisasi nama dengan <code>@{{nama}}</code>.</small><small class="muted" data-character-count>0 / 4.000</small></div>
            </div>
            <aside class="messaging-compose-options">
                <h3>Opsi pengiriman</h3>
                <label>Batas pesan per tahap<input type="number" name="batchSize" min="1" max="50" value="{{ old('batchSize', 10) }}" required><small class="muted">Pengiriman diproses bertahap sesuai batas ini.</small></label>
                <label class="messaging-check"><input type="checkbox" name="useBanner" value="1" @checked(old('useBanner', false))><span>Sertakan banner</span></label>
                <img class="messaging-banner" src="{{ $settings['defaultBannerUrl'] }}" alt="Pratinjau banner" data-banner hidden loading="lazy">
                @if($settings['interactiveCtaEnabled'])<label class="messaging-check"><input type="checkbox" name="useInteractiveCta" value="1" @checked(old('useInteractiveCta', false))><span>Sertakan tombol tautan<small class="muted">Tambahkan tautan HTTPS pada isi pesan.</small></span></label>@else<input type="checkbox" name="useInteractiveCta" hidden disabled>@endif
            </aside>
        </div>
        <fieldset>
            <legend>Pilih penerima</legend>
            <div class="messaging-section-heading"><small class="muted">Hanya kontak aktif yang menyetujui informasi WhatsApp.</small><span class="messaging-selection-count"><strong data-selected-count>0</strong> dipilih</span></div>
            <div class="messaging-search"><input type="search" data-recipient-search placeholder="Cari nama atau nomor" aria-label="Cari penerima"><button type="button" class="btn btn-secondary" data-recipient-load>Cari</button></div>
            <div class="actions"><button type="button" class="btn btn-secondary" data-select-page>Pilih halaman ini</button><button type="button" class="btn btn-secondary" data-clear-selection>Hapus pilihan</button></div>
            <div data-recipient-list aria-live="polite" aria-busy="true"><p class="muted">Memuat kontak…</p></div>
            <div class="actions messaging-recipient-pagination"><button type="button" class="btn btn-secondary" data-recipient-prev disabled>Sebelumnya</button><small class="muted" data-recipient-page></small><button type="button" class="btn btn-secondary" data-recipient-next disabled>Berikutnya</button></div>
        </fieldset>
        <div data-contact-inputs></div>
        <div class="messaging-alert" data-compose-error hidden role="alert"></div>
        <section class="messaging-preview" data-final-preview hidden aria-live="polite"></section>
        <div class="messaging-section"><div class="actions"><button type="button" class="btn btn-secondary" data-preview>Tinjau pesan</button><button class="btn btn-primary" data-create disabled>Buat draft</button></div><small class="muted">Draft tidak langsung dikirim. Mulai pengiriman dari halaman detail campaign.</small></div>
    </form>
</section>
@endsection
