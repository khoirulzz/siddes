<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page {
    margin: 10mm 15mm 22mm 22mm;
}

body {
    font-family: "timesnewroman";
    font-size: 12pt;
    color: #000;
    line-height: 1.45;
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
    margin-bottom: 10px;
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
    margin-top: 8px;
    margin-bottom: 6px;
}

.judul .nama {
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    font-size: 14pt;
    margin: 0;
}

.judul .nomor {
    margin-top: 2px; /* DIKECILKAN */
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
    width: 195px;
}

.identitas .sep {
    width: 12px;
    text-align: center;
}

/* =========================
   TANDA TANGAN
========================= */

.ttd {
    margin-top: 20px;
}

.ttd table {
    width: 100%;
}

.ttd td {
    vertical-align: top;
    text-align: center;
}
.ttd-gap {
    margin-top: 70px;   /* atau 50px */
}


.spasi-ttd {
    height: 80px;
}
.text-justify
{
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
                <img src="{{ $logoUrl }}" style="height:95px; width:auto;">
            </td>
            <td class="kop-text">
                <p class="kop-l1">PEMERINTAH KABUPATEN PEKALONGAN</p>
                <p class="kop-l2">KECAMATAN PANINGGARAN</p>
                <p class="kop-l3">DESA LAMBANGGELUN</p>
                <p class="kop-l4">
                    Sekretariat : Jln Raya Lambanggelun - Paninggaran KP 51164
                </p>
            </td>
        </tr>
    </table>
</div>

<!-- =========================
     JUDUL
========================= -->

<div class="judul">
    <div class="nama">SURAT PENGANTAR KEHILANGAN</div>
    <div class="nomor">
        Nomor: {{ $letterNumber }}
    </div>
</div>

<!-- =========================
     IDENTITAS PEJABAT
========================= -->

<p>Yang bertanda tan gan di bawah ini:</p>

<table class="identitas">
    <tr><td class="label">Nama</td><td class="sep">:</td><td>{{ $data['nama_kepala_desa'] }}</td></tr>
    <tr><td class="label">Jabatan</td><td class="sep">:</td><td>{{ \Illuminate\Support\Str::upper($data['jabatan_kepala_desa']) }}</td></tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="sep">:</td>
        <td class="text-justify">{{ $villageAddress }}</td>
    </tr>
</table>

<p>Dengan ini menerangkan bahwa:</p>

<!-- =========================
     IDENTITAS PEMOHON
========================= -->

<table class="identitas">
    <tr><td class="label">Nama</td><td class="sep">:</td><td>{{ $data['nama_pemohon'] }}</td></tr>
    <tr><td class="label">Tempat Tgl lahir</td><td class="sep">:</td><td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td></tr>
    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $data['nik'] }}</td></tr>
    <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td>{{ $data['jenis_kelamin'] }}</td></tr>
    <tr><td class="label">Agama</td><td class="sep">:</td><td>{{ $data['agama'] }}</td></tr>
    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $data['pekerjaan'] }}</td></tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="sep">:</td>
        <td class="text-justify">
            Dk. {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }}
            {{ $villageName }}, {{ $districtName }},
            {{ $regencyName }}
        </td>
    </tr>
    <tr>
        <td class="label">Keperluan</td>
        <td class="sep">:</td>
        <td>Membuat Surat Kehilangan {{ $data['kehilangan_benda'] }}</td>
    </tr>
    <tr>
        <td class="label">Bukti Dukung</td>
        <td class="sep">:</td>
        <td>Fotocopy KK</td>
    </tr>
</table>

<p>
    Orang tersebut diatas benar-benar Penduduk Desa Lambanggelun Kecamatan Paninggaran
    Kabupaten Pekalongan, dan mengaku kehilangan <strong>{{ $data['kehilangan_benda'] }}</strong>
    di {{ $data['lokasi_kehilangan'] }} lebih dari {{ $data['lama_hilang'] }}
    yang lalu sampai sekarang belum di temukan.
</p>

<p>
    Demikian Surat Keterangan ini dibuat untuk dipergunakan seperlunya.
</p>


<div style="margin-top:30px;">

    <!-- BARIS ATAS -->
    <div style="width:100%;">
        <!-- KIRI -->
        <div style="width:50%; float:left; text-align:center;">
            <br><br> <!-- INI KUNCI: NURUNIN "YANG MELAPOR" -->
            Yang Melapor
        </div>

        <!-- KANAN -->
        <div style="width:50%; float:right; text-align:center;">
            Lambanggelun, {{ $data['tanggal'] }}<br>
            Mengetahui<br>
            {{ $data['jabatan_kepala_desa'] }}
        </div>

        <div style="clear:both;"></div>
    </div>

    <br><br><br><br> <!-- JARAK TANDA TANGAN -->

    <!-- BARIS NAMA -->
    <div style="width:100%;">
        <!-- NAMA PELAPOR -->
        <div style="width:50%; float:left; text-align:center;">
            <strong>{{ $data['nama_pemohon'] }}</strong>
        </div>

        <!-- NAMA KEPALA DESA -->
        <div style="width:50%; float:right; text-align:center;">
            <strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong>
        </div>

        <div style="clear:both;"></div>
    </div>

</div>

</body>
</html>
