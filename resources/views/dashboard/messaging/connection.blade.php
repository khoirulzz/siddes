@extends('dashboard.messaging.layout')
@section('messaging-content')
@php
    $connection = \App\Support\MessagingLabels::connection($state);
    $pairing = in_array($state['status'] ?? null, ['CONNECTING', 'QR_READY']);
    $connected = ($state['status'] ?? null) === 'CONNECTED';
@endphp
<section class="panel messaging-connection" @if($pairing) data-refresh-seconds="5" @endif>
    <div class="toolbar"><div><h2>Koneksi WhatsApp</h2><p>Kelola akun WhatsApp pengirim informasi desa.</p></div><a class="btn btn-secondary" href="{{ url()->full() }}">Perbarui status</a></div>
    <div class="messaging-connection-hero messaging-connection-hero--{{ $connection['tone'] }}" data-connection-state="{{ $connection['tone'] }}" role="status">
        @include('dashboard.messaging.connection-indicator')
        <div class="messaging-connection-copy"><h3>{{ $connection['title'] }}</h3><p>{{ $connection['description'] }}</p>@if(!empty($state['phoneNumber']))<span class="messaging-connection-phone">+{{ $state['phoneNumber'] }}</span>@endif</div>
    </div>
    @if($connection['notice'] && !$connection['ready'])<div class="messaging-connection-notice">{{ $connection['notice'] }}</div>@endif
    @if(!empty($state['qrDataUrl']) && ($state['status'] ?? null)==='QR_READY' && $connection['tone']==='pairing')
    <div class="messaging-pairing">
        <div class="messaging-pairing-code"><img class="messaging-qr" src="{{ $state['qrDataUrl'] }}" alt="Kode QR untuk menautkan akun WhatsApp"><span class="muted">Pindai melalui aplikasi WhatsApp di ponsel.</span></div>
        <div><h3>Tautkan akun dalam tiga langkah</h3><ol class="messaging-pairing-steps"><li>Buka WhatsApp atau WhatsApp Business di ponsel.</li><li>Pilih <strong>Perangkat tertaut</strong>, lalu <strong>Tautkan perangkat</strong>.</li><li>Arahkan kamera ke kode QR di samping.</li></ol><p class="muted">Halaman akan memperbarui status setelah akun berhasil terhubung.</p></div>
    </div>
    @endif
    <div class="messaging-connection-footer">
        <div class="actions">
            @if($connection['ready'])<a class="btn btn-primary" href="{{ route('dashboard.messaging.campaigns.create') }}">Buat campaign</a>@endif
            <form method="POST" action="{{ route('dashboard.messaging.connection.action', $connected ? 'disconnect' : 'connect') }}" data-confirm="{{ $connected ? 'Putuskan koneksi WhatsApp? Pengiriman menunggu sampai akun terhubung kembali.' : 'Hubungkan akun WhatsApp?' }}">@csrf<button class="btn {{ $connected ? 'btn-secondary' : 'btn-primary' }}" @disabled($pairing)>{{ $connected ? 'Putuskan koneksi' : ($pairing ? 'Menunggu koneksi…' : 'Hubungkan WhatsApp') }}</button></form>
        </div>
        <small class="muted">{{ $connection['ready'] ? 'Koneksi aktif tidak berarti pesan langsung dikirim. Pengiriman dimulai dari campaign.' : 'Pesan dalam antrean tetap menunggu hingga koneksi siap.' }}</small>
    </div>
</section>
@endsection
