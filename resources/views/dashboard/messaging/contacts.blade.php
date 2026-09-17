@extends('dashboard.messaging.layout')
@section('messaging-content')
<div class="messaging-workspace">
    <section class="panel">
        <div class="toolbar"><div><h2>Kontak bersama</h2><p>Daftar penerima untuk seluruh informasi desa.</p></div><button type="button" class="btn btn-secondary" data-toggle-import aria-expanded="false" aria-controls="messaging-import">Import Excel / CSV</button></div>
        <form class="messaging-search" method="GET"><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau nomor" aria-label="Cari kontak"><button class="btn btn-secondary">Cari</button>@if(request('search'))<a class="btn btn-secondary" href="{{ route('dashboard.messaging.contacts') }}">Reset</a>@endif</form>
        <div class="messaging-table"><table><thead><tr><th>Nama perwakilan</th><th>WhatsApp</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse($items as $item)
            <tr><td><strong>{{ $item['fullName'] }}</strong></td><td>+{{ $item['phoneNormalized'] }}</td><td><span class="messaging-status {{ $item['isActive'] && $item['whatsappOptIn'] ? 'messaging-status--ready' : 'messaging-status--warning' }}">{{ !$item['isActive'] ? 'Nonaktif' : ($item['whatsappOptIn'] ? 'Siap menerima' : 'Belum setuju') }}</span></td><td><div class="actions">
                <a class="btn btn-secondary" href="{{ route('dashboard.messaging.contacts', ['edit'=>$item['id'], 'search'=>request('search'), 'page'=>request('page')]) }}">Edit</a>
                <form method="POST" action="{{ route('dashboard.messaging.contacts.update', $item['id']) }}" data-confirm="Ubah status kontak ini?">@csrf @method('PATCH')<input type="hidden" name="isActive" value="{{ $item['isActive'] ? 0 : 1 }}"><button class="btn btn-secondary">{{ $item['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
            </div></td></tr>
            @empty<tr><td colspan="4" class="messaging-empty">Belum ada kontak yang cocok.</td></tr>@endforelse
        </tbody></table></div>
        @include('dashboard.messaging.pagination', ['paginator'=>$items])
    </section>
    <aside class="panel">
        <h2>{{ $edit ? 'Edit kontak' : 'Tambah kontak' }}</h2>
        <form class="messaging-form" method="POST" action="{{ $edit ? route('dashboard.messaging.contacts.update', $edit['id']) : route('dashboard.messaging.contacts.store') }}">
            @csrf @if($edit) @method('PATCH') @endif
            <label>Nama perwakilan<input name="fullName" value="{{ old('fullName', $edit['fullName'] ?? '') }}" required minlength="2" maxlength="120" autocomplete="name"></label>
            <label>Nomor WhatsApp<input type="tel" name="phone" value="{{ old('phone', $edit['phone'] ?? '') }}" required maxlength="30" inputmode="tel" placeholder="08…" autocomplete="tel"></label>
            <input type="hidden" name="whatsappOptIn" value="0">
            <label class="messaging-check"><input type="checkbox" name="whatsappOptIn" value="1" @checked(old('whatsappOptIn', $edit['whatsappOptIn'] ?? false))><span>Penerima setuju menerima informasi desa melalui WhatsApp</span></label>
            <div class="actions"><button class="btn btn-primary">Simpan kontak</button>@if($edit)<a class="btn btn-secondary" href="{{ route('dashboard.messaging.contacts') }}">Batal edit</a>@endif</div>
        </form>
    </aside>
</div>
<section class="panel" id="messaging-import" data-import-panel hidden>
    <h2>Import kontak</h2><p class="muted">Kolom: Nama Lengkap, Nomor WhatsApp, Opt In. Maksimal 1.000 baris / 5 MB. Persetujuan kosong tidak dihitung sebagai setuju.</p>
    <label>Pilih file<input type="file" accept=".csv,.xlsx" data-import-file></label>
    <div data-import-preview aria-live="polite"></div>
    <button type="button" class="btn btn-primary" data-import-submit data-url="{{ route('dashboard.messaging.contacts.import') }}" disabled>Import kontak</button>
</section>
@endsection
