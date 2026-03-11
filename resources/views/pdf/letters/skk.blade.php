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
    margin-bottom: 14px;
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
    margin-top: 6px;
    margin-bottom: 8px;
}

.judul .nama {
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    font-size: 14pt;
    margin: 0;
}

.judul .nomor {
    margin-top: 0;
    font-size: 11pt;
}

/* =========================
   ISI
========================= */

p {
    margin: 6px 0;
    text-align: justify;
}

.data {
    width: 100%;
    margin: 6px 0;
}

.data td {
    padding: 2px 0;
    vertical-align: top;
}

.data .label {
    width: 220px;
}

.data .sep {
    width: 12px;
    text-align: center;
}

/* =========================
   TANDA TANGAN
========================= */
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
    <div class="nama">SURAT KETERANGAN KEMATIAN</div>
    <div class="nomor">
        Nomor: {{ $letterNumber }}
    </div>
</div>

<p>Yang bertanda tangan dibawah ini, menerangkan bahwa :</p>

<!-- DATA ALMARHUM -->
<table class="data">
    <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td>{{ $data['nama_almarhum'] }}</td></tr>
    <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td>{{ $data['jenis_kelamin_almarhum'] }}</td></tr>
    <tr>
        <td class="label">Tgl. Lahir / Umur</td>
        <td class="sep">:</td>
        <td>{{ $data['tgl_lahir_almarhum'] }} / {{ $data['usia_almarhum'] }} thn</td>
    </tr>
    <tr><td class="label">Alamat</td><td class="sep">:</td><td class="text-justify">{{ $data['alamat_almarhum'] }}</td></tr>
</table>

<p>Telah meninggal dunia pada :</p>

<!-- DATA MENINGGAL -->
<table class="data">
    <tr><td class="label">Hari</td><td class="sep">:</td><td>{{ $data['hari_meninggal'] }}</td></tr>
    <tr><td class="label">Tanggal</td><td class="sep">:</td><td>{{ $data['tgl_meninggal'] }}</td></tr>
    <tr><td class="label">Pukul</td><td class="sep">:</td><td>{{ $data['waktu_meninggal'] }}</td></tr>
    <tr><td class="label">Bertempat di</td><td class="sep">:</td><td>{{ $data['tempat_meninggal'] }}</td></tr>
    <tr><td class="label">Penyebab Kematian</td><td class="sep">:</td><td>{{ $data['penyebab_meninggal'] }}</td></tr>
</table>

<p>Surat Keterangan ini dibuat berdasarkan keterangan pelapor :</p>

<!-- DATA PELAPOR -->
<table class="data">
    <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td>{{ $data['nama_pemohon'] }}</td></tr>
    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $data['nik'] }}</td></tr>
    <tr>
        <td class="label">Tgl. Lahir / Umur</td>
        <td class="sep">:</td>
        <td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }} / {{ $data['usia_pemohon'] }} thn</td>
    </tr>
    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $data['pekerjaan'] }}</td></tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="sep">:</td>
        <td class="text-justify">
            Dk {{ $data['dusun'] }}, RT {{ $data['rt'] }} RW {{ $data['rw'] }},
            {{ $villageName }}
        </td>
    </tr>
</table>

<p>
    Hubungan pelapor dengan yang meninggal :
    <strong>{{ $data['hubungan_pemohon'] }}</strong>
</p>

<p>
    Demikian surat keterangan kematian ini dibuat dengan sebenar-benarnya agar
    dapat dipergunakan sebagaimana mestinya.
</p>

<!-- =========================
     TTD (STABIL mPDF)
========================= -->

<div style="margin-top:30px;">

    <div style="width:100%;">
        <div style="width:50%; float:left; text-align:center;">
            <br><br>
            Yang Melapor
        </div>

        <div style="width:50%; float:right; text-align:center;">
            Lambanggelun, {{ $data['tanggal'] }}<br><br>
            {{ $data['jabatan_kepala_desa'] }}
        </div>

        <div style="clear:both;"></div>
    </div>

    <br><br><br><br>

    <div style="width:100%;">
        <div style="width:50%; float:left; text-align:center;">
            <strong>{{ $data['nama_pemohon'] }}</strong>
        </div>

        <div style="width:50%; float:right; text-align:center;">
            <strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong>
        </div>

        <div style="clear:both;"></div>
    </div>

</div>

</body>
</html>
