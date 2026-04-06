<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body{
    font-family: DejaVu Sans, sans-serif;
    font-size:12px;
}

table{
    width:100%;
    border-collapse: collapse;
}

th,td{
    border-bottom:1px solid #ddd;
    padding:6px;
}

.header{
    margin-bottom:20px;
}

.right{
    text-align:right;
}

.total{
    margin-top:20px;
}

.footer{
    margin-top:40px;
}
</style>
</head>
<body>
@php
    $isPembelian = blank($nota->jenis_penjualan);
@endphp

<table class="header">
<tr>
<td width="70%">
<h3>{{ $isPembelian ? 'INVOICE PEMBELIAN' : 'INVOICE PENJUALAN' }}</h3>

<strong>Kepada</strong><br>
@if($isPembelian)
{{ $nota->vendor->namavendor ?? '-' }}<br>
{{ $nota->vendor->alamat ?? '' }}<br>
{{ $nota->vendor->telp ?? '' }}
@else
{{ $nota->customerToko->nama_lengkap ?? '-' }}<br>
{{ $nota->customerToko->alamat ?? '' }}<br>
{{ $nota->customerToko->no_hp ?? '' }}
@endif

</td>

<td class="right">

@if($logoPath)
<img src="{{ $logoPath }}" height="60">
@endif

<br><br>

Nomor : {{ $nota->nota_no }} <br>
Tanggal : {{ $nota->tanggal }} <br>
Jatuh Tempo : {{ $nota->tgl_tempo }}

</td>
</tr>
</table>

<table>
<thead>
<tr>
<th>#</th>
@if($isPembelian)
<th>Kode Transaksi</th>
<th>Deskripsi</th>
@else
<th>Deskripsi</th>
@endif
<th>Vol</th>
<th>Harga</th>
<th>Total</th>
</tr>
</thead>

<tbody>
@foreach($nota->transactions as $i => $trx)
<tr>
<td>{{ $i+1 }}</td>
@if($isPembelian)
<td>{{ $trx->kodeTransaksi ? '(' . $trx->kodeTransaksi->kodetransaksi . ') ' . $trx->kodeTransaksi->transaksi : '-' }}</td>
<td>{{ $trx->description }}</td>
@else
<td>{{ $trx->description }}</td>
@endif
<td>{{ $trx->jml }}</td>
<td class="right">Rp {{ number_format($trx->nominal,0,',','.') }}</td>
<td class="right">Rp {{ number_format($trx->total,0,',','.') }}</td>
</tr>
@endforeach
</tbody>
</table>

<table class="total">
<tr>
<td width="80%" class="right"><strong>Total</strong></td>
<td class="right">
<strong>Rp {{ number_format($nota->total,0,',','.') }}</strong>
</td>
</tr>
</table>

<div class="footer">
Mengetahui,
<br><br><br>

________________________
</div>

</body>
</html>
