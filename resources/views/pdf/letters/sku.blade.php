<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page {
    margin: 10mm 15mm 22mm 22mm;
}

body {
    font-family: "Times New Roman", Times, serif;
    font-size: 12pt;
    color: #000;
    line-height: 1.65;
}

table {
    border-collapse: collapse;
}

/* =========================
   KOP SURAT
========================= */

.kop {
    border-bottom: 3px solid #000;
    padding-bottom: 8px;
    margin-bottom: 6px;
}

.kop td {
    vertical-align: middle;
}

.logo {
    width: 110px;
    text-align: center;
}

.kop-text {
    text-align: center;
    padding-right: 20px;
}

.kop-l1 {
    font-size: 16pt;
    text-transform: uppercase;
    margin: 0;
}

.kop-l2 {
    font-size: 16pt;
    text-transform: uppercase;
    margin: 0;
}

.kop-l3 {
    font-size: 18pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 0;
}

.kop-l4 {
    font-size: 11pt;
    font-style: italic;
    margin-top: 4px;
}

/* =========================
   JUDUL + KODE DESA
========================= */

.judul {
    text-align: center;
    margin: 0;
}

.judul .nama {
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    font-size: 14pt;
}

.judul .nomor {
    margin-top: 4px;
    font-size: 11pt;
}

/* =========================
   ISI
========================= */

p {
    margin: 6px 0;
    text-align: justify;
}

.identitas {
    width: 100%;
    margin-top: 8px;
    margin-bottom: 8px;
}

.identitas td {
    padding: 2px 0;
    vertical-align: top;
}

.identitas .label {
    width: 180px;
}

.identitas .titik {
    width: 10px;
    text-align: center;
}

/* =========================
   TANDA TANGAN
========================= */

.ttd {
    width: 300px;
    margin-left: auto;
    margin-top: 20px;
}

.ttd p {
    margin: 0;
    text-align: left;
}

.jarak-ttd {
    height: 90px;
}
</style>
</head>

<body>

<!-- =========================
     KOP SURAT
========================= -->

<div class="kop">
    <table width="100%">
        <tr>
            <td class="logo">
                <img src="{{ public_path('assets/images/logo_pekalongan.svg') }}"
                     style="height:95px; width:auto;">
            </td>
            <td class="kop-text">
                <p class="kop-l1">PEMERINTAH KABUPATEN PEKALONGAN</p>
                <p class="kop-l2">KECAMATAN PANINGGARAN</p>
                <p class="kop-l3">DESA LAMBANGGELUN</p>
                <p class="kop-l4">
                    Sekretariat : Jl. Raya Lambanggelun - Paninggaran - Pekalongan KP 51164
                </p>
            </td>
        </tr>
    </table>
</div>

<!-- =========================
     KODE DESA + JUDUL
========================= -->

<table width="100%" style="margin-top:6px; margin-bottom:6px;">
    <tr>
        <td style="width:150px; font-size:9pt; line-height:1.2; vertical-align:top;">
            Kode Desa<br>
            33.26.02.13
        </td>

        <td style="text-align:center; vertical-align:top;">
            <div class="judul">
                <div class="nama"><span style="font-weight: bold; text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 4px;">SURAT KETERANGAN USAHA </span></div>
                <div class="nomor">Nomor : {{ $letterNumber }}</div>
            </div>
        </td>
        <td style="width:150px;"></td>
    </tr>
</table>

<p>
    Dasar keterangan dari pemohon, dengan ini Kepala {{ $villageName }},
    {{ $districtName }} {{ $regencyName }}, dengan ini menerangkan bahwa :
</p>

<table class="identitas">
    <tr>
        <td class="label">Nama</td>
        <td class="titik">:</td>
        <td>{{ $data['nama_pemohon'] }}</td>
    </tr>
    <tr>
        <td class="label">Tempat Tgl lahir</td>
        <td class="titik">:</td>
        <td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td>
    </tr>
    <tr>
        <td class="label">NIK</td>
        <td class="titik">:</td>
        <td>{{ $data['nik'] }}</td>
    </tr>
    <tr>
        <td class="label">Jenis Kelamin</td>
        <td class="titik">:</td>
        <td>{{ $data['jenis_kelamin'] }}</td>
    </tr>
    <tr>
        <td class="label">Agama</td>
        <td class="titik">:</td>
        <td>{{ $data['agama'] }}</td>
    </tr>
    <tr>
        <td class="label">Pekerjaan</td>
        <td class="titik">:</td>
        <td>{{ $data['pekerjaan'] }}</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="titik">:</td>
        <td>
           Dk. {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }}
             {{ $villageName }}, {{ $districtName }},
            {{ $regencyName }}
        </td>
    </tr>
</table>

<p>
    Adalah benar penduduk yang berdomisili di {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }}
    {{ $villageName }}, {{ $districtName }}, {{ $regencyName }}.
</p>

<p>
    Berdasarkan Keterangan dari pemohon nama tersebut di atas adalah memiliki usaha
    <strong>{{ $data['nama_usaha'] }}</strong> di wilayah {{ $villageName }}, {{ $districtName }}, {{ $regencyName }}.
</p>

<p>
    Adapun Surat Keterangan Usaha dipergunakan untuk
    <strong>{{ $data['keperluan'] }}</strong>.
</p>

<p>
    Demikian surat ini dibuat agar dipergunakan untuk sebagaimana perlunya.
</p>

<!-- =========================
     TANDA TANGAN
========================= -->

<div class="ttd">
    <p>Dikeluarkan di : {{ $villageName }}</p>
    <p>Pada tanggal : {{ $data['tanggal'] }}</p>
    <br>
    <p>Kepala {{ $villageName }}</p>
    <div class="jarak-ttd"></div>
    <p><strong>ABDUL HADI</strong></p>
</div>

</body>
</html>
