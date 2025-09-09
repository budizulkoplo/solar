<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nota {{ $transaksi->no_struk }}</title>
<style>
@media print {
    .btn-row { display: none !important; }
}
</style>
</head>
<body>
    <div class="paper">
        <h3 style="text-align:center">NOTA {{ $transaksi->no_struk }}</h3>
        <p>Nopol: {{ $transaksi->armada->nopol ?? '-' }}</p>
        <p>Tanggal: {{ \Carbon\Carbon::parse($transaksi->tgl_transaksi)->format('d-m-Y H:i') }}</p>
        <p>Volume: {{ number_format($volumeM3,2) }} m³</p>
        <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR">

        <div class="btn-row">
            <button onclick="window.print()">Cetak</button>
        </div>
    </div>

<script>
window.addEventListener('load', function(){
    setTimeout(() => { window.print(); }, 300);
});

// setelah print atau cancel → close tab
window.addEventListener('afterprint', function(){
    window.close();
});
</script>
</body>
</html>
