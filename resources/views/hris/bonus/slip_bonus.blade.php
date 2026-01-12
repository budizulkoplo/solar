<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Bonus</title>
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
    </style>
</head>
<body>

    {{-- Header Perusahaan --}}
    <div class="text-center">
        @if(!empty($setting['logo']))
           <img src="storage/{{$setting['logo']}}" alt="Logo" width="65"><br><br>
        @endif
        <strong>{{ $setting['company_name'] }}</strong><br>
        Slip Bonus - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </div>

    {{-- Identitas Pegawai --}}
    <p>
        <strong>NIK:</strong> {{ $user->nik }}<br>
        <strong>Nama:</strong> {{ $user->name }}<br>
        <strong>NIP:</strong> {{ $user->nip ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $user->jabatan ?? '-' }}<br><br>
    </p>

    {{-- Detail Bonus --}}
    <div class="bg-header">Detail Bonus</div>
    <table>
        @foreach($bonuses as $index => $bonus)
        <tr>
            <td class="label">{{ $index + 1 }}. {{ $bonus->keterangan }}</td>
            <td class="value">Rp {{ number_format($bonus->nominal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        
        <tr class="total-row">
            <td>TOTAL BONUS</td>
            <td class="value">
                Rp {{ number_format($totalBonus, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    {{-- Catatan --}}
    <div class="text-center">
        <div class="highlight">Total Bonus</div>
        <div class="highlight">
            Rp {{ number_format($totalBonus, 0, ',', '.') }}
        </div>
        <div class="note">Bonus akan ditambahkan ke gaji bulan berikutnya</div>
        <div class="note">Slip ini dicetak secara otomatis, simpan untuk arsip.</div>
        <br>
    </div>

    <div class="text-right text-muted">
        <small>Kendal, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

</body>
</html>