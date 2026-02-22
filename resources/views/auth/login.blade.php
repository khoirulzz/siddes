@extends('layouts.public')

@section('title', 'Login Dashboard')

@section('content')
    <div class="section-title">
        <h2>Login Dashboard</h2>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label style="display:flex;align-items:center;gap:0.4rem;margin-bottom:1rem;">
                <input type="checkbox" name="remember" value="1" style="width:auto;margin:0;">
                Ingat saya
            </label>

            <button class="btn btn-primary" type="submit">Masuk</button>
            <p class="help" style="margin-top:0.8rem;">
                Akun awal: admin@lambanggelun.id / password123
            </p>
        </form>
    </div>
@endsection
