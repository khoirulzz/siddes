@extends('dashboard.messaging.layout')
@section('messaging-content')
<section class="panel" @if(collect($items->items())->contains(fn($m)=>in_array($m['status'], ['QUEUED','PROCESSING']))) data-refresh-seconds="15" @endif>
    <div class="toolbar"><div><h2>Riwayat pengiriman</h2><p>Periksa proses antrean dan konfirmasi pengantaran.</p></div><a class="btn btn-secondary" href="{{ url()->full() }}">Segarkan</a></div>
    <form class="messaging-search" method="GET">
        @if(request('campaignId'))<input type="hidden" name="campaignId" value="{{ request('campaignId') }}">@endif
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nomor atau isi pesan" aria-label="Cari riwayat">
        <select name="status" aria-label="Filter status"><option value="">Semua status</option>@foreach(['QUEUED','PROCESSING','SENT','SERVER_ACK','DELIVERED','READ','FAILED','UNKNOWN','SKIPPED','CANCELLED'] as $value)<option value="{{ $value }}" @selected(request('status')===$value)>{{ \App\Support\MessagingLabels::status($value) }}</option>@endforeach</select>
        <button class="btn btn-secondary">Terapkan</button>
        @if(request('search') || request('status'))<a class="btn btn-secondary" href="{{ route('dashboard.messaging.history', request()->only('campaignId')) }}">Reset</a>@endif
    </form>
    @include('dashboard.messaging.messages')
    @include('dashboard.messaging.pagination', ['paginator'=>$items])
</section>
@endsection
