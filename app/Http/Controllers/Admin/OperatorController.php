<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.operators.index', [
            'items' => User::where('role', 'operator')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.operators.form', [
            'item' => new User(['role' => 'operator']),
            'method' => 'POST',
            'route' => route('dashboard.operators.store'),
            'title' => 'Tambah Operator',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['role'] = 'operator';
        User::create($data);

        return redirect()->route('dashboard.operators.index')->with('success', 'Operator berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $operator)
    {
        abort_if($operator->role !== 'operator', 404);

        return view('dashboard.operators.form', [
            'item' => $operator,
            'method' => 'PUT',
            'route' => route('dashboard.operators.update', $operator),
            'title' => 'Edit Operator',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $operator)
    {
        abort_if($operator->role !== 'operator', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($operator->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $operator->update($data);

        return redirect()->route('dashboard.operators.index')->with('success', 'Operator berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $operator)
    {
        abort_if($operator->role !== 'operator', 404);

        $operator->delete();

        return redirect()->route('dashboard.operators.index')->with('success', 'Operator berhasil dihapus.');
    }
}
