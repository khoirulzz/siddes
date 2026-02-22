@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    <section class="panel">
        <form method="POST" action="{{ $route }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="form-grid">
                <div class="field">
                    <label for="name">Nama</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $item->name) }}" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $item->email) }}" required>
                </div>

                <div class="field">
                    <label for="password">Password {{ $method === 'POST' ? '' : '(opsional)' }}</label>
                    <input id="password" type="password" name="password" {{ $method === 'POST' ? 'required' : '' }}>
                </div>

                <div class="field">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" {{ $method === 'POST' ? 'required' : '' }}>
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.operators.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
