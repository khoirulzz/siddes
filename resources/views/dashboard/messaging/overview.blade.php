@extends('dashboard.messaging.layout')
@section('messaging-content')
@php($connection = \App\Support\MessagingLabels::connection($data['whatsapp'] ?? []))
<section class="panel">
    <div class="toolbar"><div><h2>Ringkasan pengiriman</h2><p>Kelola informasi desa dan pantau pengirimannya.</p></div><a class="btn btn-primary" href="{{ route('dashboard.messaging.campaigns.create') }}">Buat campaign</a></div>
    <div class="messaging-connection-compact">
        @include('dashboard.messaging.connection-indicator')
        <div><strong>{{ $connection['title'] }}</strong><p class="muted">{{ $connection['description'] }}</p>@if(auth()->user()->isAdmin())<a href="{{ route('dashboard.messaging.connection') }}">Kelola koneksi</a>@endif</div>
    </div>
</section>
<div class="messaging-metrics">@foreach(['contacts'=>'Kontak aktif','activeCampaigns'=>'Campaign berjalan','queued'=>'Dalam antrean','sent'=>'Diserahkan','delivered'=>'Terkirim','read'=>'Dibaca','failed'=>'Perlu perhatian'] as $field=>$label)<section class="panel"><span>{{ $label }}</span><strong>{{ number_format($data[$field] ?? 0) }}</strong></section>@endforeach</div>
@if($pending->isNotEmpty())<section class="panel"><h2>Draft yang perlu diperiksa</h2>@foreach($pending as $submission)<p>{{ $submission->created_at->format('d/m/Y H:i') }} <a class="btn btn-secondary" href="{{ route('dashboard.messaging.campaigns.reconcile', $submission->request_uuid) }}">Periksa draft</a></p>@endforeach</section>@endif
@endsection
