@extends('layouts.dashboard')

@section('title', 'Edit Data PBB')
@section('page_title', 'Edit Data PBB')

@section('content')
    <section class="panel">
        <form action="{{ route('dashboard.pbb-tax-objects.update', $taxObject) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="field">
                    <label for="nop">NOP</label>
                    <input id="nop" type="text" name="nop" value="{{ old('nop', $taxObject->nop) }}" required>
                </div>
                <div class="field">
                    <label for="tax_year">Tahun Pajak</label>
                    <input id="tax_year" type="number" name="tax_year" min="2026" max="{{ date('Y') + 1 }}" value="{{ old('tax_year', $taxObject->tax_year) }}" required>
                </div>
                <div class="field">
                    <label for="nama_wp_sppt">Nama WP SPPT</label>
                    <input id="nama_wp_sppt" type="text" name="nama_wp_sppt" value="{{ old('nama_wp_sppt', $taxObject->nama_wp_sppt ?: $taxObject->owner_name ?: $taxObject->tax_name) }}" required>
                </div>
                <div class="field">
                    <label for="desa_wp_sppt">Desa WP SPPT</label>
                    <input id="desa_wp_sppt" type="text" name="desa_wp_sppt" value="{{ old('desa_wp_sppt', $taxObject->desa_wp_sppt ?: config('village.name', 'Desa Lambanggelun')) }}" required>
                </div>
                <div class="field full">
                    <label for="jalan_wp_sppt">Jalan WP SPPT</label>
                    <input id="jalan_wp_sppt" type="text" name="jalan_wp_sppt" value="{{ old('jalan_wp_sppt', $taxObject->jalan_wp_sppt ?: $taxObject->tax_address) }}" required>
                </div>
                <div class="field">
                    <label for="rt_wp_sppt">RT WP SPPT</label>
                    <input id="rt_wp_sppt" type="text" name="rt_wp_sppt" inputmode="numeric" value="{{ old('rt_wp_sppt', $taxObject->rt_wp_sppt) }}">
                </div>
                <div class="field">
                    <label for="rw_wp_sppt">RW WP SPPT</label>
                    <input id="rw_wp_sppt" type="text" name="rw_wp_sppt" inputmode="numeric" value="{{ old('rw_wp_sppt', $taxObject->rw_wp_sppt) }}">
                </div>
                <div class="field full">
                    <label for="jalan_op_sppt">Jalan OP SPPT</label>
                    <input id="jalan_op_sppt" type="text" name="jalan_op_sppt" value="{{ old('jalan_op_sppt', $taxObject->jalan_op_sppt ?: $taxObject->location ?: $taxObject->jalan_wp_sppt) }}" required>
                </div>
                <div class="field">
                    <label for="rt_op_sppt">RT OP SPPT</label>
                    <input id="rt_op_sppt" type="text" name="rt_op_sppt" inputmode="numeric" value="{{ old('rt_op_sppt', $taxObject->rt_op_sppt) }}">
                </div>
                <div class="field">
                    <label for="rw_op_sppt">RW OP SPPT</label>
                    <input id="rw_op_sppt" type="text" name="rw_op_sppt" inputmode="numeric" value="{{ old('rw_op_sppt', $taxObject->rw_op_sppt) }}">
                </div>
                <div class="field">
                    <label for="luas_tanah_sppt">Luas Tanah SPPT</label>
                    <input id="luas_tanah_sppt" type="number" step="0.01" min="0" name="luas_tanah_sppt" value="{{ old('luas_tanah_sppt', $taxObject->luas_tanah_sppt ?? $taxObject->land_area) }}">
                </div>
                <div class="field">
                    <label for="luas_bangunan_sppt">Luas Bangunan SPPT</label>
                    <input id="luas_bangunan_sppt" type="number" step="0.01" min="0" name="luas_bangunan_sppt" value="{{ old('luas_bangunan_sppt', $taxObject->luas_bangunan_sppt ?? $taxObject->building_area) }}">
                </div>
                <div class="field">
                    <label for="pbb_terhutang">PBB Terhutang</label>
                    <input id="pbb_terhutang" type="number" step="0.01" min="0" name="pbb_terhutang" value="{{ old('pbb_terhutang', $taxObject->pbb_terhutang ?? $taxObject->amount_due) }}" required>
                </div>
                <div class="field">
                    <label for="tanggal_pembayaran">Tanggal Pembayaran (Opsional)</label>
                    <input id="tanggal_pembayaran" type="date" name="tanggal_pembayaran" value="{{ old('tanggal_pembayaran', $taxObject->tanggal_pembayaran?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="actions" style="margin-top:0.8rem;">
                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.index') }}">Kembali</a>
            </div>
        </form>
    </section>
@endsection
