<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
@page { margin:10mm 15mm 22mm 22mm; }

body{
font-family:"timesnewroman";
font-size:12pt;
line-height:1.5;
}

.judul{
text-align:center;
font-weight:bold;
font-size:14pt;
text-transform:uppercase;
margin-bottom:20px;
}

.data{
width:100%;
margin:8px 0;
}

.data td{
padding:2px 0;
vertical-align:top;
}

.label{width:220px}
.sep{width:12px;text-align:center}

p{
text-align:justify;
margin:8px 0;
}

.ttd-right{
margin-top:44px;
width:44%;
margin-left:auto;
text-align:center;
}

.ttd-name{
display:block;
margin-top:68px;
white-space:nowrap;
font-size:11pt;
}
</style>
</head>

<body>
<div class="judul">
SURAT PERNYATAAN PENGHASILAN
</div>

<p>Yang bertandatangan dibawah ini :</p>

<table class="data">
<tr>
<td class="label">Nama</td>
<td class="sep">:</td>
<td>{{ $data['nama'] }}</td>
</tr>

<tr>
<td class="label">Tempat Tanggal Lahir</td>
<td class="sep">:</td>
<td>{{ $data['tempat_lahir'] }}, {{ $data['tanggal_lahir'] }}</td>
</tr>

<tr>
<td class="label">NIK</td>
<td class="sep">:</td>
<td>{{ $data['nik'] }}</td>
</tr>

<tr>
<td class="label">Alamat</td>
<td class="sep">:</td>
<td>{{ $data['alamat'] }}</td>
</tr>

<tr>
<td class="label">Pekerjaan</td>
<td class="sep">:</td>
<td>{{ $data['pekerjaan'] }}</td>
</tr>
</table>

<p>
Menyatakan dengan sesungguhnya bahwa sampai saat surat pernyataan ini
 ditandatangani, saya menyatakan bahwa jumlah gaji / upah pokok saya adalah
 sebesar <strong>{{ $data['jumlah_penghasilan'] }}</strong> per bulan.
</p>

<p>
Demikianlah pernyataan ini saya buat dengan sebenarnya tanpa paksaan dari
pihak manapun dan apabila di kemudian hari pernyataan saya ini tidak benar,
maka saya bersedia dituntut secara perundangan yang berlaku.
</p>

<div class="ttd-right">
Yang menyatakan
<br><br>
Materai<br>
10000
<span class="ttd-name"><strong>{{ $data['nama'] }}</strong></span>
</div>

</body>
</html>
