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
    width: 85px;
    text-align: center;
}

.kop-text {
    text-align: center;
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
   JUDUL
========================= */

.judul {
    text-align: center;
    margin-top: 10px;
    margin-bottom: 10px;
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
    margin: 6px 0;
}

.identitas td {
    padding: 2px 0;
    vertical-align: top;
}

.identitas .label {
    width: 190px;
}

.identitas .titik {
    width: 12px;
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
    height: 80px;
}

.identitas .isi-justify {
    text-align: justify;
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
                <img src="{{ $logoUrl }}"
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

<table width="100%" style="margin-top:6px; margin-bottom:6px;">
    <tr>
        <!-- KODE DESA -->
        <td style="width:150px; font-size:9pt; line-height:1.2; vertical-align:top;">
            Kode Desa<br>
            33.26.02.13
        </td>

        <!-- JUDUL + NOMOR -->
        <td style="text-align:center; vertical-align:top;">
            <div class="judul" style="margin:0;">
                <div class="nama"><span style="font-weight: bold; text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 4px;">
                    SURAT KETERANGAN TIDAK MAMPU</span>
                </div>
                <div class="nomor" style="margin-top:2px;">
                    Nomor : {{ $letterNumber }}
                </div>
            </div>
        </td>

        <!-- SPACER KANAN (BIAR CENTER BENERAN) -->
        <td style="width:150px;"></td>
    </tr>
</table>

<!-- =========================
     IDENTITAS PEJABAT
========================= -->
<p style="margin-top:3px;">Yang bertandatangan di bawah ini :</p>
<table class="identitas">
    <tr><td class="label">Nama</td><td class="titik">:</td><td>{{ $data['nama_kepala_desa'] }}</td></tr>
    <tr><td class="label">Jenis kelamin</td><td class="titik">:</td><td>Laki-laki</td></tr>
    <tr><td class="label">Jabatan</td><td class="titik">:</td><td>{{ \Illuminate\Support\Str::upper($data['jabatan_kepala_desa']) }}</td></tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="titik">:</td>
        <td>{{ $villageAddress }}</td>
    </tr>
</table>

<p style="margin-top:6px;">Menerangkan Bahwa :</p>

<!-- =========================
     IDENTITAS PEMOHON
========================= -->

<table class="identitas">
    <tr><td class="label">Nama</td><td class="titik">:</td><td>{{ $data['nama_pemohon'] }}</td></tr>
    <tr><td class="label">Tempat, tanggal Lahir</td><td class="titik">:</td><td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td></tr>
    <tr><td class="label">NIK</td><td class="titik">:</td><td>{{ $data['nik'] }}</td></tr>
    <tr><td class="label">Agama</td><td class="titik">:</td><td>{{ $data['agama'] }}</td></tr>
    <tr><td class="label">Pekerjaan</td><td class="titik">:</td><td>{{ $data['pekerjaan'] }}</td></tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="titik">:</td>
        <td class="isi-justify">
            Dk. {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }} 
            {{ $villageName }}, {{ $districtName }}, {{ $regencyName }}
        </td>
    </tr>
    <tr>
        <td class="label">Keperluan</td>
        <td class="titik">:</td>
        <td class="isi-justify">Surat keterangan ini dibuat sebagai syarat {{ $data['keperluan'] }}</td>
    </tr>
    <tr>
        <td class="label">Keterangan Lain</td>
        <td class="titik">:</td>
        <td class="isi-justify">
            Menerangkan bahwa orang tersebut diatas benar-benar warga Desa Lambanggelun
            dari keluarga kurang mampu dan yang bersangkutan sudah terdaftar di DTKS.
        </td>
    </tr>
</table>

<p>
    Demikian keterangan ini dibuat agar digunakan sebagaimana mestinya.
</p>

<!-- =========================
     TANDA TANGAN
========================= -->

<div class="ttd">
    <p>Lambanggelun, {{ $data['tanggal'] }}</p>
    <p>{{ $data['jabatan_kepala_desa'] }}</p>
    <div class="jarak-ttd"></div>
    <p><strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong></p>
</div>

</body>
</html>

