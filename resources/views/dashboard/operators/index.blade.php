@extends('layouts.dashboard')

@section('title', 'Kelola Operator')
@section('page_title', 'Kelola Operator')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Daftar Operator</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.operators.create') }}">Tambah Operator</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->email }}</td>
                        <td>{{ $item->role }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary" href="{{ route('dashboard.operators.edit', $item) }}">Edit</a>
                                <form action="{{ route('dashboard.operators.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus operator ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada akun operator.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
