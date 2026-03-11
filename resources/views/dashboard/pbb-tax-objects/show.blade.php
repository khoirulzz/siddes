@extends('layouts.dashboard')

@section('title', 'Detail Data PBB')
@section('page_title', 'Detail Data PBB')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Detail NOP {{ $taxObject->nop }}</h2>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.edit', $taxObject) }}">Edit</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.index') }}">Kembali</a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <tbody>
                    <tr><th style="width:280px;">Tahun Pajak</th><td>{{ $taxObject->resolvedTaxYear() }}</td></tr>
                    <tr><th>NOP</th><td>{{ $taxObject->nop }}</td></tr>
                    <tr><th>Nama WP SPPT</th><td>{{ $taxObject->nama_wp_sppt ?: '-' }}</td></tr>
                    <tr><th>Jalan WP SPPT</th><td>{{ $taxObject->jalan_wp_sppt ?: '-' }}</td></tr>
                    <tr><th>RT/RW WP SPPT</th><td>{{ $taxObject->rt_wp_sppt ?: '-' }} / {{ $taxObject->rw_wp_sppt ?: '-' }}</td></tr>
                    <tr><th>Desa WP SPPT</th><td>{{ $taxObject->desa_wp_sppt ?: '-' }}</td></tr>
                    <tr><th>Jalan OP SPPT</th><td>{{ $taxObject->jalan_op_sppt ?: '-' }}</td></tr>
                    <tr><th>RT/RW OP SPPT</th><td>{{ $taxObject->rt_op_sppt ?: '-' }} / {{ $taxObject->rw_op_sppt ?: '-' }}</td></tr>
                    <tr><th>Luas Tanah SPPT</th><td>{{ number_format($taxObject->resolvedLandArea(), 2, ',', '.') }}</td></tr>
                    <tr><th>Luas Bangunan SPPT</th><td>{{ number_format($taxObject->resolvedBuildingArea(), 2, ',', '.') }}</td></tr>
                    <tr><th>PBB Terhutang</th><td>Rp {{ number_format($taxObject->resolvedAmountDue(), 0, ',', '.') }}</td></tr>
                    <tr><th>Tanggal Pembayaran</th><td>{{ $taxObject->resolvedPaidAt()?->format('d-m-Y') ?: '-' }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
