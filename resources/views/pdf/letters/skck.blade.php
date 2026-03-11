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
    margin-top:4px;
    font-size:11pt;
}

.data{
    width:100%;
    margin:8px 0;
}

.data td{
    padding:2px 0;
    vertical-align:top;
}

.data .label{width:210px}
.data .sep{width:12px;text-align:center}

p{
    margin:6px 0;
    text-align:justify;
}

.ttd-table{
    width:100%;
    margin-top:26px;
}

.ttd-table td{
    vertical-align:top;
    text-align:center;
}

.nowrap{ white-space:nowrap; }
</style>
</head>

<body>
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
Sekretariat : Jln Raya Lambanggelun - Paninggaran KP 51164
</p>
</td>
</tr>
</table>
</div>

<div class="judul">
<div class="nama">SURAT PENGANTAR PERMOHONAN SKCK</div>
<div class="nomor">
Nomor : {{ $pdfLetterNumber ?? $letterNumber }}
</div>
</div>

<p>Dengan ini menerangkan bahwa :</p>

<table class="data">
<tr>
<td class="label">Nama lengkap</td>
<td class="sep">:</td>
<td>{{ $data['nama_pemohon'] }}</td>
</tr>

<tr>
<td class="label">Tempat / Tanggal lahir</td>
<td class="sep">:</td>
<td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td>
</tr>

<tr>
<td class="label">Jenis Kelamin</td>
<td class="sep">:</td>
<td>{{ $data['jenis_kelamin'] }}</td>
</tr>

<tr>
<td class="label">NIK</td>
<td class="sep">:</td>
<td>{{ $data['nik'] }}</td>
</tr>

<tr>
<td class="label">Agama</td>
<td class="sep">:</td>
<td>{{ $data['agama'] }}</td>
</tr>

<tr>
<td class="label">Status</td>
<td class="sep">:</td>
<td>{{ $data['status_kawin'] }}</td>
</tr>

<tr>
<td class="label">Pekerjaan</td>
<td class="sep">:</td>
<td>{{ $data['pekerjaan'] }}</td>
</tr>

<tr>
<td class="label">Alamat</td>
<td class="sep">:</td>
<td>
Dk. {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }},
{{ $villageName }}, {{ $districtName }}, {{ $regencyName }}
</td>
</tr>

<tr>
<td class="label">No HP</td>
<td class="sep">:</td>
<td>{{ $data['no_hp'] }}</td>
</tr>

<tr>
<td class="label">Email</td>
<td class="sep">:</td>
<td>{{ $data['email'] }}</td>
</tr>

<tr>
<td class="label">Keperluan</td>
<td class="sep">:</td>
<td>{{ $data['keperluan'] }}</td>
</tr>

<tr>
<td class="label">Keterangan lain-lain</td>
<td class="sep">:</td>
<td>
Orang tersebut benar benar warga Desa Lambanggelun Kecamatan Paninggaran dan berkelakuan baik.
</td>
</tr>
</table>

<p>
Demikian Surat Keterangan ini dibuat untuk digunakan seperlunya.
</p>

<table class="ttd-table">
<tr>
<td style="width:34%;">
Pemohon
</td>
<td style="width:32%;padding-top:18px;">
Mengetahui<br>
Camat Paninggaran
</td>
<td style="width:34%;">
<span class="nowrap">Lambanggelun, {{ $data['tanggal'] }}</span><br>
{{ $data['jabatan_kepala_desa'] }}
</td>
</tr>
<tr>
<td style="height:72px;"></td>
<td></td>
<td></td>
</tr>
<tr>
<td>
<strong>{{ $data['nama_pemohon'] }}</strong>
</td>
<td>
<strong>..............................................</strong>
</td>
<td>
<strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong>
</td>
</tr>
</table>

</body>
</html>
