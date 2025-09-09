@php
    use App\Models\Setting;
    $setting = Setting::first();
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nota {{ $transaksi->no_struk }}</title>

<style>
    /* layout untuk thermal 80mm */
    body { margin:0; font-family: monospace; background:#fff; }
    .paper { width: 320px; margin:0 auto; padding: 8px 12px; box-sizing: border-box;
             color: #000; font-size:13px; }
    .txt-center{ text-align:center; }
    .txt-right{ text-align:right; padding-right:6px; }
    .txt-left{ text-align:left; }
    hr { border: none; border-top: 1px dashed #000; margin:6px 0; }
    table { width:100%; border-collapse:collapse; }
    td { vertical-align: top; padding:2px 0; }
    .small { font-size:11px; color:#333; }
    .bold { font-weight:700; }
    .btn-row { display:flex; gap:8px; margin:8px 0; }
    .btn { flex:1; padding:8px; background:#007bff; color:white; border-radius:4px;
           text-align:center; text-decoration:none; display:inline-block; font-size:13px; }
    .btn.secondary { background:#6c757d; }
    img.qr { display:block; margin:6px auto; max-width:140px; }

    /* Sembunyikan tombol saat print */
    @media print {
        .btn-row { display:none !important; }
    }
    .btn.success {
        background: #28a745;   /* hijau */
        color: #fff;
    }

</style>

</head>
<body>
<div class="paper" id="paper">
    <div class="txt-center bold" style="font-size:14px;">{{ $setting->nama_perusahaan ?? 'PERUSAHAAN' }}</div>
    <div class="txt-center small">{{ $setting->alamat ?? '' }}</div>
    <hr>

    <table>
        <tr><td>No Struk</td><td>:</td><td class="txt-right">{{ $transaksi->no_struk }}</td></tr>
        <tr><td>Nopol</td><td>:</td><td class="txt-right">{{ $transaksi->armada->nopol ?? '-' }}</td></tr>
        <tr><td>Tanggal</td><td>:</td><td class="txt-right">{{ \Carbon\Carbon::parse($transaksi->tgl_transaksi)->format('d-m-Y H:i') }}</td></tr>
        <tr><td>Operator</td><td>:</td><td class="txt-right">{{ $transaksi->user->name ?? '-' }}</td></tr>
    </table>

    <hr>

    <table>
        <tr><td>Panjang</td><td class="txt-right">{{ $transaksi->panjang }} cm</td></tr>
        <tr><td>Lebar</td><td class="txt-right">{{ $transaksi->lebar }} cm</td></tr>
        <tr><td>Tinggi</td><td class="txt-right">{{ $transaksi->tinggi }} cm</td></tr>
        <tr><td>Plus</td><td class="txt-right">{{ $transaksi->plus }} cm</td></tr>
        <tr><td class="bold">Volume</td><td class="txt-right bold">{{ number_format($volumeM3,2) }} m³</td></tr>
    </table>

    <hr>

    <div class="txt-center">
        <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}" alt="QR">
    </div>

    <br>
    <div class="txt-center small">*** {{ $project->nama_project ?? ($project->nama ?? '-') }} ***</div>

    <div class="btn-row">
        <a href="#" id="btnPrint" class="btn">Cetak</a>
        <a href="#" id="btnShare" class="btn secondary">Share/Printer App</a>
        <a href="{{ route('mobile.transaksi_armada.create') }}" class="btn success">Input Baru</a>
    </div>


</div>

<script>
    // Auto print on load
    window.addEventListener('load', function(){
        setTimeout(function(){ 
            window.print(); 
        }, 400);
    });

    // auto close setelah print selesai
    window.addEventListener('afterprint', function(){
        window.close();
    });

    // tombol cetak manual
    document.getElementById('btnPrint').addEventListener('click', function(e){
        e.preventDefault();
        window.print();
    });

    // share via Web Share API (mobile)
    document.getElementById('btnShare').addEventListener('click', async function(e){
        e.preventDefault();
        const plain = `
{{ $setting->nama_perusahaan ?? 'PERUSAHAAN' }}
No: {{ $transaksi->no_struk }}
Nopol: {{ $transaksi->armada->nopol ?? '-' }}
Tgl: {{ \Carbon\Carbon::parse($transaksi->tgl_transaksi)->format('d-m-Y H:i') }}
Panjang: {{ $transaksi->panjang }} cm
Lebar: {{ $transaksi->lebar }} cm
Tinggi: {{ $transaksi->tinggi }} cm
Plus: {{ $transaksi->plus }} cm
Volume: {{ number_format($volumeM3,2) }} m3
Project: {{ $project->nama_project ?? ($project->nama ?? '-') }}
        `.trim();

        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'Nota ' + '{{ $transaksi->no_struk }}',
                    text: plain
                });
            } catch(err) {
                alert('Share dibatalkan atau gagal: ' + err);
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(plain);
            alert('Teks nota disalin ke clipboard. Buka aplikasi printer (mis. RawBT) dan paste/print.');
        } catch (err) {
            alert('Tidak bisa salin otomatis. Silakan copy manual:\n\n' + plain);
        }
    });
</script>

</body>
</html>
