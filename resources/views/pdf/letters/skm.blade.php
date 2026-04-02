<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page { margin: 10mm 15mm 22mm 22mm; }

body{
    font-family:"timesnewroman";
    font-size:12pt;
    color:#000;
    line-height:1.45;
}

table{border-collapse:collapse}

/* =========================
   KOP SURAT
========================= */

.kop{
    border-bottom:3px solid #000;
    padding-bottom:8px;
    margin-bottom:14px;
}

.kop td{vertical-align:middle}

.logo{
    width:85px;
    text-align:center;
}

.kop-text{text-align:center}

.kop-l1{margin:0;font-size:16pt;text-transform:uppercase}
.kop-l2{margin:0;font-size:16pt;text-transform:uppercase}
.kop-l3{margin:0;font-size:18pt;font-weight:bold;text-transform:uppercase}

.kop-l4{
    margin-top:4px;
    font-size:11pt;
    font-style:italic;
}

/* =========================
   JUDUL
========================= */

.judul{
    text-align:center;
    margin-top:12px;
    margin-bottom:14px;
}

.judul .nama{
    font-weight:bold;
    text-transform:uppercase;
    text-decoration:underline;
    font-size:14pt;
}

.judul .nomor{
    margin-top:1px;
    font-size:11pt;
}

/* =========================
   DATA
========================= */

.data{
    width:100%;
    margin:8px 0;
}

.data td{
    padding:2px 0;
    vertical-align:top;
}

.no{width:20px}

.label{width:190px}

.sep{width:12px;text-align:center}

p{
    margin:6px 0;
    text-align:justify;
}

/* =========================
   SAKSI
========================= */

.saksi{
    margin-top:10px;
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
<img src="{{ $logoUrl }}" style="height:95px;width:auto;">
</td>

<td class="kop-text">
<p class="kop-l1">PEMERINTAH KABUPATEN PEKALONGAN</p>
<p class="kop-l2">KECAMATAN PANINGGARAN</p>
<p class="kop-l3">DESA LAMBANGGELUN</p>
<p class="kop-l4">
Sekretariat : Jln Raya Lambanggelun - Paninggaran - Pekalongan KP 51164
</p>
</td>

</tr>
</table>
</div>

<!-- =========================
     JUDUL
========================= -->

<div class="judul">
<div class="nama">SURAT KETERANGAN MENIKAH</div>
<div class="nomor">
Nomor : {{ $pdfLetterNumber ?? $letterNumber }}
</div>
</div>

<p>
Yang bertanda tangan di bawah ini, {{ $data['jabatan_kepala_desa'] }}
{{ $villageName }} {{ $districtName }} {{ $regencyName }} menerangkan bahwa :
</p>

<!-- DATA SUAMI -->

<table class="data">

<tr>
<td class="no">1.</td>
<td class="label">Nama</td>
<td class="sep">:</td>
<td>{{ $data['nama_suami'] }} BIN {{ $data['ayah_suami'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Tempat Tanggal Lahir</td>
<td class="sep">:</td>
<td>{{ $data['tempat_lahir_suami'] }}, {{ $data['tanggal_lahir_suami'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">NIK</td>
<td class="sep">:</td>
<td>{{ $data['nik_suami'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Pekerjaan</td>
<td class="sep">:</td>
<td>{{ $data['pekerjaan_suami'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Alamat</td>
<td class="sep">:</td>
<td>{{ $data['alamat_suami'] }}</td>
</tr>

</table>

<!-- DATA ISTRI -->

<table class="data">

<tr>
<td class="no">2.</td>
<td class="label">Nama</td>
<td class="sep">:</td>
<td>{{ $data['nama_istri'] }} BINTI {{ $data['ayah_istri'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Tempat Tanggal Lahir</td>
<td class="sep">:</td>
<td>{{ $data['tempat_lahir_istri'] }}, {{ $data['tanggal_lahir_istri'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">NIK</td>
<td class="sep">:</td>
<td>{{ $data['nik_istri'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Pekerjaan</td>
<td class="sep">:</td>
<td>{{ $data['pekerjaan_istri'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Alamat</td>
<td class="sep">:</td>
<td>{{ $data['alamat_istri'] }}</td>
</tr>

</table>

<p>
Adalah benar nama tersebut di atas penduduk Desa Lambanggelun Kecamatan
Paninggaran Kabupaten Pekalongan dan sepanjang pengetahuan kami bahwa
yang bersangkutan telah menikah pada tanggal
<strong>{{ $data['tanggal_nikah'] }}</strong>
dengan mas kawin sebesar
<strong>{{ $data['mas_kawin'] }}</strong>.
</p>

<p>
Demikian keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
</p>

<!-- =========================
     TTD
========================= -->

<div style="margin-top:30px;">

<div style="width:100%;">

<div style="width:50%;float:left;">
Saksi :
<br><br>

1. {{ $data['saksi_suami'] }} ({{ $data['hub_dg_suami'] }}) &nbsp;&nbsp;&nbsp;&nbsp;(………………...)

<br><br>

2. {{ $data['saksi_istri'] }} ({{ $data['hub_dg_istri'] }}) &nbsp;&nbsp;&nbsp;&nbsp;(………………...)

</div>

<div style="width:50%;float:right;text-align:center;">
Lambanggelun, {{ $data['tanggal'] }}
<br><br>
{{ $data['jabatan_kepala_desa'] }}
<br><br><br><br><br>
<strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong>
</div>

<div style="clear:both"></div>

</div>

</div>

</body>
</html>
