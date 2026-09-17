@extends('dashboard.messaging.layout')
@section('messaging-content')
<section class="panel" @if($campaign['status']==='RUNNING') data-refresh-seconds="15" @endif>
    <div class="toolbar"><div><h2>{{ $campaign['name'] }}</h2><p><span class="messaging-status">{{ \App\Support\MessagingLabels::status($campaign['status']) }}</span> {{ $campaign['recipientCount'] }} penerima · {{ $campaign['batchSize'] }} pesan per tahap</p></div><a class="btn btn-secondary" href="{{ route('dashboard.messaging.campaigns') }}">Kembali</a></div>
    <div class="messaging-options">@foreach($campaign['jobs'] as $status=>$count)<span class="messaging-status">{{ \App\Support\MessagingLabels::status($status) }}: <strong>{{ $count }}</strong></span>@endforeach</div>
    <div class="messaging-section"><h3>Isi campaign</h3>@include('dashboard.messaging.message-content', ['text'=>$campaign['contentSnapshot'] ?? 'Campaign lama: isi tersedia pada riwayat penerima.'])</div>
    <div class="messaging-section">
        <div class="actions">
            @foreach(['DRAFT'=>['start'=>'Mulai pengiriman'],'RUNNING'=>['pause'=>'Jeda pengiriman'],'PAUSED'=>['resume'=>'Lanjutkan pengiriman']] as $state=>$actions)
                @if($campaign['status']===$state)
                    @foreach($actions as $action=>$label)
                    <form method="POST" action="{{ route('dashboard.messaging.campaigns.action', [$campaign['id'], $action]) }}" data-confirm="{{ $action==='start' ? 'Mulai campaign? Pesan akan masuk proses pengiriman bertahap.' : 'Ubah status campaign ini?' }}">@csrf<button class="btn btn-primary">{{ $label }}</button></form>
                    @endforeach
                @endif
            @endforeach
            @if(in_array($campaign['status'], ['DRAFT','RUNNING','PAUSED']))
            <form method="POST" action="{{ route('dashboard.messaging.campaigns.action', [$campaign['id'], 'cancel']) }}" data-confirm="Batalkan campaign? Pesan yang sudah dikirim tidak dapat ditarik kembali.">@csrf<button class="btn btn-danger">Batalkan campaign</button></form>
            @endif
            <a class="btn btn-secondary" href="{{ url()->full() }}">Segarkan</a>
        </div>
        @if($campaign['status']==='DRAFT')<p class="muted">Draft belum dikirim. Pilih Mulai pengiriman untuk memproses pesan secara bertahap.</p>@endif
    </div>
</section>
<section class="panel">
    <div class="toolbar"><h2>Status penerima</h2></div>
    <form class="messaging-search" method="GET"><select name="status" aria-label="Filter status penerima"><option value="">Semua status</option>@foreach(['QUEUED','PROCESSING','SENT','DELIVERED','READ','FAILED','UNKNOWN','SKIPPED','CANCELLED'] as $value)<option value="{{ $value }}" @selected(request('status')===$value)>{{ \App\Support\MessagingLabels::status($value) }}</option>@endforeach</select><button class="btn btn-secondary">Terapkan</button></form>
    @include('dashboard.messaging.messages', ['items'=>$messages])
    @include('dashboard.messaging.pagination', ['paginator'=>$messages])
</section>
@endsection
