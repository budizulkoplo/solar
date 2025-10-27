<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        body {
            font-family: sans-serif;
            font-size: 11px;
            width: 100%;
            max-width: 75mm;
            margin: auto;
            padding: 5px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
        .highlight { font-weight: bold; font-size: 13px; margin: 5px 0; }
        .note { font-size: 10px; margin-top: 3px; }
        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            text-align: center;
        }
        .watermark img { height: 100%; }
    </style>
</head>
<body>

    {{-- Header Perusahaan --}}
    <div class="text-center">
        @if(!empty($unitkerja->logo))
            <div class="text-center mb-2">
                <img src="{{ public_path($unitkerja->logo) }}" alt="Logo" width="65">
            </div>
        @endif
        <strong>{{ $setting['company_name'] }}</strong><br>
        Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </div>

    {{-- Identitas Pegawai --}}
    <p>
        <strong>NIP:</strong> {{ $rekap->user->nip ?? '-' }}<br>
        <strong>Nama:</strong> {{ $rekap->nama ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $rekap->user->jabatan ?? '-' }}<br><br>

    </p>

    {{-- Pendapatan --}}
    <div class="bg-header">Pendapatan</div>
    <table>
        <tr><td class="label">Gaji Pokok</td><td class="value">Rp {{ number_format($rekap->gajipokok) }}</td></tr>
        <tr><td class="label">Pek. Tambahan</td><td class="value">Rp {{ number_format($rekap->pek_tambahan) }}</td></tr>
        <tr><td class="label">Masa Kerja</td><td class="value">Rp {{ number_format($rekap->masakerja) }}</td></tr>
        <tr><td class="label">Komunikasi</td><td class="value">Rp {{ number_format($rekap->komunikasi) }}</td></tr>
        <tr><td class="label">Transportasi</td><td class="value">Rp {{ number_format($rekap->transportasi) }}</td></tr>
        <tr><td class="label">Konsumsi</td><td class="value">Rp {{ number_format($rekap->konsumsi) }}</td></tr>
        <tr><td class="label">Tunj. Asuransi</td><td class="value">Rp {{ number_format($rekap->tunj_asuransi) }}</td></tr>
        <tr><td class="label">Jabatan</td><td class="value">Rp {{ number_format($rekap->jabatan) }}</td></tr>
        <tr class="total-row">
            <td>Total Pendapatan</td>
            <td class="value">
                Rp {{ number_format(
                    $rekap->gajipokok + $rekap->pek_tambahan + $rekap->masakerja + 
                    $rekap->komunikasi + $rekap->transportasi + $rekap->konsumsi + 
                    $rekap->tunj_asuransi + $rekap->jabatan
                ) }}
            </td>
        </tr>
    </table>

    {{-- Potongan --}}
    <div class="bg-header">Potongan</div>
    <table>
        <tr><td class="label">Cicilan</td><td class="value">Rp {{ number_format($rekap->cicilan) }}</td></tr>
        <tr><td class="label">Asuransi</td><td class="value">Rp {{ number_format($rekap->asuransi) }}</td></tr>
        <tr><td class="label">Zakat</td><td class="value">Rp {{ number_format($rekap->zakat) }}</td></tr>
        <tr class="total-row">
            <td>Total Potongan</td>
            <td class="value">
                Rp {{ number_format($rekap->cicilan + $rekap->asuransi + $rekap->zakat) }}
            </td>
        </tr>
    </table>

    {{-- Total Diterima --}}
    <div class="text-center">
        <div class="highlight">Total Diterima</div>
        <div class="highlight">
            Rp {{ number_format(
                ($rekap->gajipokok + $rekap->pek_tambahan + $rekap->masakerja + 
                $rekap->komunikasi + $rekap->transportasi + $rekap->konsumsi + 
                $rekap->tunj_asuransi + $rekap->jabatan) - 
                ($rekap->cicilan + $rekap->asuransi + $rekap->zakat)
            ) }}
        </div>
        <div class="note">Slip ini dicetak secara otomatis, simpan untuk arsip.</div>
        <br>
    </div>

    <div class="text-right text-muted">
        <small>Kendal, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

</body>
</html>
