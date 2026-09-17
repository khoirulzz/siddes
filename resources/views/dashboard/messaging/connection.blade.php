@extends('dashboard.messaging.layout')
@section('messaging-content')
<section class="panel" @if(in_array($state['status'], ['CONNECTING','QR_READY'])) data-refresh-seconds="5" @endif>
    <div class="toolbar"><div><h2>Koneksi WhatsApp</h2><p><span class="messaging-status {{ $state['status']==='CONNECTED' ? 'messaging-status--ready' : 'messaging-status--warning' }}">{{ \App\Support\MessagingLabels::status($state['status']) }}</span>@if(!empty($state['phoneNumber'])) +{{ $state['phoneNumber'] }}@endif</p></div><a class="btn btn-secondary" href="{{ url()->full() }}">Segarkan</a></div>
    @if(($state['authPersistence'] ?? null)==='degraded')<div class="messaging-alert" role="alert">Penyimpanan session bermasalah. Pengiriman dihentikan untuk menjaga keamanan koneksi.</div>
    @elseif(($state['authPersistence'] ?? null)==='healthy')<p class="muted">Session tersimpan dengan baik.</p>@endif
    @if(!empty($state['reason']))<details><summary>Detail kondisi koneksi</summary><p class="muted">{{ $state['reason'] }}</p></details>@endif
    @if(!empty($state['qrDataUrl']))<img class="messaging-qr" src="{{ $state['qrDataUrl'] }}" alt="QR untuk menautkan perangkat WhatsApp"><p>Buka WhatsApp → Perangkat tertaut → Tautkan perangkat.</p>@endif
    <div class="messaging-section"><form method="POST" action="{{ route('dashboard.messaging.connection.action', $state['status']==='CONNECTED' ? 'disconnect' : 'connect') }}" data-confirm="{{ $state['status']==='CONNECTED' ? 'Hentikan koneksi? Session tetap tersimpan.' : 'Hubungkan perangkat WhatsApp?' }}">@csrf<button class="btn {{ $state['status']==='CONNECTED' ? 'btn-secondary' : 'btn-primary' }}" @disabled(in_array($state['status'], ['CONNECTING','QR_READY']))>{{ $state['status']==='CONNECTED' ? 'Hentikan koneksi' : (in_array($state['status'], ['CONNECTING','QR_READY']) ? 'Menunggu koneksi…' : 'Hubungkan WhatsApp') }}</button></form></div>
    <p class="muted">Gunakan satu instance API untuk session ini. Jangan menjalankan API lokal dengan database produksi yang sama.</p>
</section>
@endsection
