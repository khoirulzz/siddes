@extends('layouts.dashboard')
@section('title', 'Pesan WhatsApp')
@section('page_title', 'Pesan WhatsApp')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/messaging.css') }}?v={{ substr(hash_file('sha256', public_path('assets/css/messaging.css')), 0, 12) }}">
<div class="messaging-module" data-messaging-module>
    <nav class="messaging-tabs" aria-label="Menu pesan WhatsApp">
        @foreach(['index'=>'Ringkasan','contacts'=>'Kontak','templates'=>'Template','campaigns'=>'Campaign','history'=>'Riwayat'] as $key=>$label)
        <a class="{{ request()->routeIs('dashboard.messaging.'.$key, 'dashboard.messaging.'.$key.'.*') ? 'active' : '' }}" href="{{ route('dashboard.messaging.'.$key) }}">{{ $label }}</a>
        @endforeach
        @if(auth()->user()->isAdmin())<a class="{{ request()->routeIs('dashboard.messaging.connection*') ? 'active' : '' }}" href="{{ route('dashboard.messaging.connection') }}">Koneksi</a>@endif
    </nav>
    @if(session('error'))<div class="messaging-alert" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="messaging-alert" role="alert">{{ $errors->first() }}</div>@endif
    @if(session('messaging_uncertain_key'))<p><a class="btn btn-secondary" href="{{ route('dashboard.messaging.campaigns.reconcile', session('messaging_uncertain_key')) }}">Periksa status pembuatan draft</a></p>@endif
    @if($unavailable)<section class="panel"><h2>Layanan pesan belum tersedia</h2><p>{{ $unavailable }}</p><a class="btn btn-secondary" href="{{ url()->full() }}">Coba lagi</a><p class="muted">Modul admin lainnya tetap dapat digunakan.</p></section>
    @else @yield('messaging-content') @endif
</div>
@vite('resources/js/messaging.js')
@endsection
