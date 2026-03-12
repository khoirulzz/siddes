<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page { margin:10mm 15mm 22mm 22mm; }

body{
font-family:"timesnewroman";
font-size:12pt;
line-height:1.45;
}

table{border-collapse:collapse}

.kop{
border-bottom:3px solid #000;
padding-bottom:8px;
margin-bottom:10px;
}

.logo{width:85px;text-align:center}

.kop-text{text-align:center;}

.kop-l1{margin:0;font-size:16pt;text-transform:uppercase}
.kop-l2{margin:0;font-size:16pt;text-transform:uppercase}
.kop-l3{margin:0;font-size:18pt;font-weight:bold;text-transform:uppercase}

.kop-l4{
margin-top:4px;
font-size:11pt;
font-style:italic;
}

.meta{width:100%;margin-top:6px;margin-bottom:12px;}
.meta td{vertical-align:top;}

.kode{
font-size:9pt;
text-align:left;
line-height:1.35;
}

.judul{
text-align:center;
}

.judul .nama{
font-weight:bold;
text-transform:uppercase;
text-decoration:underline;
font-size:14pt;
}

.judul .nomor{
margin-top:3px;
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

.no{width:25px}
.label{width:210px}
.sep{width:12px;text-align:center}

p{
margin:6px 0;
text-align:justify;
}

.ttd-main{width:100%;margin-top:30px;}
.ttd-main td{text-align:center;vertical-align:top;}

.ttd-rt-rw{width:70%;margin:8px auto 0;}
.ttd-rt-rw td{text-align:center;vertical-align:top;}
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

<table class="meta">
<tr>
<td style="width:30%;" class="kode">
Kode Wilayah<br>
<strong>33.26.02.13</strong>
</td>
<td style="width:40%;" class="judul">
<div class="nama">SURAT KETERANGAN KERJA</div>
<div class="nomor">No : {{ $pdfLetterNumber ?? $letterNumber }}</div>
</td>
<td style="width:30%;"></td>
</tr>
</table>

<p>Yang bertanda tangan di bawah ini :</p>

<table class="data">
<tr>
<td class="label">Nama</td>
<td class="sep">:</td>
<td>{{ $data['nama_kepala_desa'] }}</td>
</tr>

<tr>
<td class="label">Jabatan</td>
<td class="sep">:</td>
<td>{{ $data['jabatan_kepala_desa'] }}</td>
</tr>
</table>

<p>Dengan ini menerangkan bahwa :</p>

<table class="data">
<tr>
<td class="no">1.</td>
<td class="label">Nama</td>
<td class="sep">:</td>
<td>{{ $data['nama_pemohon'] }}</td>
</tr>

<tr>
<td class="no">2.</td>
<td class="label">Tempat & Tanggal lahir</td>
<td class="sep">:</td>
<td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td>
</tr>

<tr>
<td class="no">3.</td>
<td class="label">Kewarganegaraan / Agama</td>
<td class="sep">:</td>
<td>Indonesia / {{ $data['agama'] }}</td>
</tr>

<tr>
<td class="no">4.</td>
<td class="label">NIK</td>
<td class="sep">:</td>
<td>{{ $data['nik'] }}</td>
</tr>

<tr>
<td class="no">5.</td>
<td class="label">Tempat tinggal</td>
<td class="sep">:</td>
<td>
Dk. {{ $data['dusun'] }} RT {{ $data['rt'] }} RW {{ $data['rw'] }},
{{ $villageName }} {{ $districtName }} {{ $regencyName }}
</td>
</tr>

<tr>
<td class="no">6.</td>
<td class="label">Pekerjaan</td>
<td class="sep">:</td>
<td>{{ $data['pekerjaan'] }}</td>
</tr>

<tr>
<td></td>
<td class="label">Keterangan</td>
<td class="sep">:</td>
<td>
Bahwa yang bersangkutan adalah benar warga Desa Lambanggelun dan dalam kondisi sehat.
</td>
</tr>

<tr>
<td></td>
<td class="label">Keperluan</td>
<td class="sep">:</td>
<td>
Surat pengantar jalan ke {{ $data['tujuan'] }}
</td>
</tr>
</table>

<p>
Mohon kepada instansi yang berwenang agar memberikan fasilitas kepada orang tersebut.
</p>

<p>
Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
</p>

<table class="ttd-main">
<tr>
<td style="width:50%;">Tanda tangan pemegang</td>
<td style="width:50%;">Lambanggelun, {{ $data['tanggal'] }}<br>{{ $data['jabatan_kepala_desa'] }}</td>
</tr>
<tr>
<td style="height:75px;"></td>
<td></td>
</tr>
<tr>
<td><strong>{{ $data['nama_pemohon'] }}</strong></td>
<td><strong>{{ \Illuminate\Support\Str::upper($data['nama_kepala_desa']) }}</strong></td>
</tr>
</table>

<div style="text-align:center;margin-top:26px;">Mengetahui,</div>
<table class="ttd-rt-rw">
<tr>
<td style="width:50%;">RT</td>
<td style="width:50%;">RW</td>
</tr>
<tr>
<td style="height:56px;"></td>
<td></td>
</tr>
<tr>
<td><strong>{{ $data['nama_rt'] }}</strong></td>
<td><strong>{{ $data['nama_rw'] }}</strong></td>
</tr>
</table>

</body>
</html>