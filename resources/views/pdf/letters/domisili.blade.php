<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page { margin: 10mm 15mm 22mm 22mm; }

body {
    font-family: "timesnewroman";
    font-size: 12pt;
    color: #000;
    line-height: 1.45;
}

table { border-collapse: collapse; }

/* =========================
   KOP SURAT
========================= */

.kop {
    border-bottom: 3px solid #000;
    padding-bottom: 8px;
    margin-bottom: 14px;
}

.kop td { vertical-align: middle; }

.logo {
    width: 85px;
    text-align: center;
}

.kop-text { text-align: center; }

.kop-l1 {
    margin: 0;
    font-size: 16pt;
    text-transform: uppercase;
}

.kop-l2 {
    margin: 0;
    font-size: 16pt;
    text-transform: uppercase;
}

.kop-l3 {
    margin: 0;
    font-size: 18pt;
    font-weight: bold;
    text-transform: uppercase;
}

.kop-l4 {
    margin-top: 4px;
    font-size: 11pt;
    font-style: italic;
}

/* =========================
   JUDUL
========================= */

.judul {
    text-align: center;
    margin-top: 12px;
    margin-bottom: 14px;
}

.judul .nama {
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    font-size: 14pt;
}

.judul .nomor {
    margin-top: 1px;
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
    margin: 8px 0;
}

.data td {
    padding: 2px 0;
    vertical-align: top;
}

.data .no {
    width: 20px;
}

.data .label {
    width: 210px;
}

.data .sep {
    width: 12px;
    text-align: center;
}
.text-justify {
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
    <div class="nama">SURAT KETERANGAN DOMISILI</div>
    <div class="nomor">
        Nomor : {{ $letterNumber }}
    </div>
</div>

<p>Yang bertanda tangan di bawah ini, menerangkan bahwa :</p>

<table class="data">
    <tr>
        <td class="no">1.</td><td class="label">Nama</td><td class="sep">:</td>
        <td>{{ $data['nama_pemohon'] }}</td>
    </tr>
    <tr>
        <td class="no">2.</td><td class="label">Tempat & Tanggal Lahir</td><td class="sep">:</td>
        <td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td>
    </tr>
    <tr>
        <td class="no">3.</td><td class="label">NIK</td><td class="sep">:</td>
        <td>{{ $data['nik'] }}</td>
    </tr>
    <tr>
        <td class="no">4.</td><td class="label">NKK</td><td class="sep">:</td>
        <td>{{ $data['nkk'] }}</td>
    </tr>
    <tr>
        <td class="no">5.</td><td class="label">Jenis Kelamin</td><td class="sep">:</td>
        <td>{{ $data['jenis_kelamin'] }}</td>
    </tr>
    <tr>
        <td class="no">6.</td><td class="label">Kewarganegaraan & Agama</td><td class="sep">:</td>
        <td>Indonesia / {{ $data['agama'] }}</td>
    </tr>
    <tr>
        <td class="no">7.</td><td class="label">Pekerjaan</td><td class="sep">:</td>
        <td>{{ $data['pekerjaan'] }}</td>
    </tr>
    <tr>
        <td class="no">8.</td><td class="label">Alamat Asal</td><td class="sep">:</td>
        <td>
            Dk. {{ $data['dusun_asal'] }} RT {{ $data['rt_asal'] }} RW {{ $data['rw_asal'] }},
            {{ $data['desa_asal'] }}, 
            {{ $data['kecamatan_asal'] }}, 
            {{ $data['kabupaten_asal'] }}
        </td>
    </tr>
    <tr>
        <td class="no">9.</td><td class="label">Keperluan</td><td class="sep">:</td>
        <td>{{ $data['keperluan'] }}</td>
    </tr>
    <tr>
        <td class="no">10.</td><td class="label">Keterangan Lain</td><td class="sep">:</td>
        <td class="text-justify">
            Orang tersebut benar yang berdomisili di Dukuh {{ $data['dusun'] }}
            RT {{ $data['rt'] }} RW {{ $data['rw'] }}
            Desa Lambanggelun, Kecamatan Paninggaran, Kabupaten Pekalongan.
        </td>
    </tr>
</table>

<p>Demikian untuk menjadikan maklum bagi yang berkepentingan.</p>

<!-- =========================
     TTD (NON TABLE – STABIL)
========================= -->

<div style="margin-top:30px;">

    <!-- BARIS ATAS -->
    <div style="width:100%;">
        <div style="width:50%; float:left; text-align:center;">
            <br><br>
            Tanda tangan pemegang
        </div>

        <div style="width:50%; float:right; text-align:center;">
            Lambanggelun, {{ $data['tanggal'] }}<br><br>
            {{ $data['jabatan_kepala_desa'] }}
        </div>

        <div style="clear:both;"></div>
    </div>

    <br><br><br><br>
    <!-- BARIS NAMA -->
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
