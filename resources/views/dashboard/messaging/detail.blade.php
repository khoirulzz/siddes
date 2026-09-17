@extends('dashboard.messaging.layout')
@section('messaging-content')
<section class="panel" @if($campaign['status']==='RUNNING') data-refresh-seconds="15" @endif><div class="toolbar"><div><h2>{{ $campaign['name'] }}</h2><p>{{ \App\Support\MessagingLabels::status($campaign['status']) }} · {{ $campaign['recipientCount'] }} penerima · Batch {{ $campaign['batchSize'] }}</p></div><a class="btn btn-secondary" href="{{ route('dashboard.messaging.campaigns') }}">Kembali</a></div><div class="actions">
@foreach(['DRAFT'=>['start'=>'Mulai'],'RUNNING'=>['pause'=>'Jeda'],'PAUSED'=>['resume'=>'Lanjutkan']] as $state=>$actions)
@if($campaign['status']===$state)
@foreach($actions as $action=>$label)
<form method="POST" action="{{ route('dashboard.messaging.campaigns.action', [$campaign['id'], $action]) }}" data-confirm="{{ $action==='start' ? 'Mulai pengiriman pada jadwal dispatcher berikutnya?' : 'Ubah status campaign ini?' }}">@csrf<button class="btn btn-primary">{{ $label }}</button></form>
@endforeach
@endif
@endforeach
@if(in_array($campaign['status'], ['DRAFT','RUNNING','PAUSED']))<form method="POST" action="{{ route('dashboard.messaging.campaigns.action', [$campaign['id'], 'cancel']) }}" data-confirm="Batalkan campaign? Pesan yang sudah direlay tidak dapat ditarik kembali.">@csrf<button class="btn btn-danger">Batalkan</button></form>@endif<a class="btn btn-secondary" href="{{ url()->full() }}">Segarkan</a></div>
<div class="messaging-options">@foreach($campaign['jobs'] as $status=>$count)<span>{{ \App\Support\MessagingLabels::status($status) }}: <strong>{{ $count }}</strong></span>@endforeach</div><pre class="messaging-message">{{ $campaign['contentSnapshot'] ?? 'Campaign lama: isi tersedia pada riwayat penerima.' }}</pre></section>
<section class="panel"><h2>Status penerima</h2>@include('dashboard.messaging.messages', ['items'=>$messages])@include('dashboard.messaging.pagination', ['paginator'=>$messages])</section>
@endsection
