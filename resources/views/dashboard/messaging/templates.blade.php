@extends('dashboard.messaging.layout')
@section('messaging-content')
<div class="messaging-workspace">
    <section class="panel">
        <div class="toolbar"><div><h2>Template pesan</h2><p>Pesan tersimpan yang dapat disesuaikan saat membuat campaign.</p></div></div>
        <form class="messaging-search" method="GET"><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari template" aria-label="Cari template"><button class="btn btn-secondary">Cari</button>@if(request('search'))<a class="btn btn-secondary" href="{{ route('dashboard.messaging.templates') }}">Hapus filter</a>@endif</form>
        <div class="messaging-table"><table><thead><tr><th>Template</th><th>Isi pesan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse($items as $item)
            <tr><td><strong>{{ $item['name'] }}</strong></td><td class="messaging-content-cell">@include('dashboard.messaging.message-content', ['text'=>$item['content']])</td><td><span class="messaging-status {{ $item['isActive'] ? 'messaging-status--ready' : '' }}">{{ $item['isActive'] ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="actions">
                <a class="btn btn-secondary" href="{{ route('dashboard.messaging.templates', ['edit'=>$item['id'], 'search'=>request('search'), 'page'=>request('page')]) }}">Edit</a>
                <form method="POST" action="{{ route('dashboard.messaging.templates.update', $item['id']) }}" data-confirm="Ubah status template ini?">@csrf @method('PATCH')<input type="hidden" name="isActive" value="{{ $item['isActive'] ? 0 : 1 }}"><button class="btn btn-secondary">{{ $item['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
            </div></td></tr>
            @empty<tr><td colspan="4" class="messaging-empty">Belum ada template yang cocok.</td></tr>@endforelse
        </tbody></table></div>
        @include('dashboard.messaging.pagination', ['paginator'=>$items])
    </section>
    <aside class="panel"><h2>{{ $edit ? 'Edit template' : 'Tambah template' }}</h2>
        <form class="messaging-form" method="POST" action="{{ $edit ? route('dashboard.messaging.templates.update', $edit['id']) : route('dashboard.messaging.templates.store') }}">
            @csrf @if($edit) @method('PATCH') @endif
            <label>Nama template<input name="name" value="{{ old('name', $edit['name'] ?? '') }}" required minlength="2" maxlength="100"></label>
            <label>Isi pesan<textarea name="content" rows="8" maxlength="4000" required>{{ old('content', $edit['content'] ?? '') }}</textarea><small class="muted">Gunakan <code>@{{nama}}</code> untuk nama penerima.</small></label>
            <div class="actions"><button class="btn btn-primary">Simpan template</button>@if($edit)<a class="btn btn-secondary" href="{{ route('dashboard.messaging.templates') }}">Batal edit</a>@endif</div>
        </form>
    </aside>
</div>
@endsection
