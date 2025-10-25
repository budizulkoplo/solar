<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        @page {
            size: 80mm auto; /* lebar 80mm */
            margin: 5mm;
        }
        body {
            font-family: sans-serif;
            font-size: 11px;
            width: 100%;
            max-width: 75mm;
            margin: auto;
            padding: 5px;
            position: relative;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        h5, h4 { margin: 2px 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #666; font-size: 10px; }
        .fw-bold { font-weight: bold; }
        .bg-header { background-color: #f4f4f4; padding: 3px 5px; font-weight: bold; margin: 8px 0 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        td, th { padding: 2px 0; vertical-align: top; }
        .label { width: 60%; }
        .value { width: 40%; text-align: right; }
        .total-row td { font-weight: bold; border-top: 1px solid #000; padding-top: 3px; }
        .total-potongan td { font-weight: bold; border-top: 1px solid #000; padding-top: 3px; }
        .highlight { font-weight: bold; font-size: 13px; margin: 5px 0; }
        .note { font-size: 10px; margin-top: 3px; }
        /* img { max-height: 55px; margin-bottom: 2px; } */
        p { margin: 5px 0; }

        /* === Watermark logo di belakang === */
        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
     
            text-align: center;
        }
        .watermark img {
            height: 100%;
        }
    </style>
</head>
<body>

    {{-- Watermark Logo --}}
    @if(!empty($setting->path_logo))
        <div class="watermark">
            <img src="{{ public_path('/'.$setting->path_logo) }}" alt="Watermark">
        </div>
    @endif

    {{-- Header Perusahaan --}}
    <div class="text-center">
        @if(!empty($setting->path_logo))
           <img src="{{ public_path('/'.$setting->path_logo) }}" alt="Logo" width="65"><br><br>
        @endif
        <strong>{{ $setting->nama_perusahaan }}</strong><br>
        Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </div>

    {{-- Identitas Pegawai --}}
    <p>
        <br>
        <strong>NIP:</strong> {{ $user->nip ?? '-' }}<br>
        <strong>Nama:</strong> {{ $pegawai->nama ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $user->jabatan ?? '-' }}<br>
        <strong>Penempatan:</strong> {{ $unitkerja->namaunit ?? '-' }}<br>
    </p>

    {{-- Pendapatan --}}
    <div class="bg-header">Pendapatan</div>
    <table>
        <tr><td class="label">Gaji Pokok</td><td class="value">Rp {{ number_format($rekap->gaji) }}</td></tr>
        <tr><td class="label">Tunjangan</td><td class="value">Rp {{ number_format($rekap->tunjangan) }}</td></tr>
        <tr><td class="label">Lembur</td><td class="value">Rp {{ number_format($rekap->nominallembur) }}</td></tr>
        <tr><td class="label">HLN</td><td class="value">Rp {{ number_format($rekap->hln) }}</td></tr>
        <tr class="total-row">
            <td>Total Pendapatan</td>
            <td class="value">
                Rp {{ number_format($rekap->gaji + $rekap->tunjangan + $rekap->nominallembur + $rekap->hln) }}
            </td>
        </tr>
    </table>

    {{-- Potongan --}}
    <div class="bg-header">Potongan</div>
    <table>
        <tr><td class="label">BPJS Kes</td><td class="value">Rp {{ number_format($rekap->bpjs_kes) }}</td></tr>
        <tr><td class="label">BPJS TK</td><td class="value">Rp {{ number_format($rekap->bpjs_tk) }}</td></tr>
        <tr><td class="label">Kasbon</td><td class="value">Rp {{ number_format($rekap->kasbon) }}</td></tr>
        <tr><td class="label">Sisa Kasbon</td><td class="value">Rp {{ number_format($rekap->sisakasbon) }}</td></tr>
        <tr class="total-potongan">
            <td>Total Potongan</td>
            <td class="value">
                Rp {{ number_format($rekap->bpjs_kes + $rekap->bpjs_tk + $rekap->kasbon + $rekap->sisakasbon) }}
            </td>
        </tr>
    </table>

    {{-- Total Diterima --}}
    <div class="text-center">
        <div class="highlight">Total Diterima</div>
        <div class="highlight">
            Rp {{ number_format(($rekap->gaji + $rekap->tunjangan + $rekap->nominallembur + $rekap->hln)
                - ($rekap->bpjs_kes + $rekap->bpjs_tk + $rekap->kasbon + $rekap->sisakasbon)) }}
        </div>
        <div class="note"><br></div>
    </div>

    <div class="text-right text-muted">
        <small>Kendal, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

</body>
</html>
