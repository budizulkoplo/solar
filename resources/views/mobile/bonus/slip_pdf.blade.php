<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Bonus</title>
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            width: 100%;
            max-width: 75mm;
            margin: auto;
            padding: 5px;
            line-height: 1.4;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #666; font-size: 10px; }
        .fw-bold { font-weight: bold; }
        .bg-header { 
            background-color: #28a745; 
            color: white; 
            padding: 5px 8px; 
            font-weight: bold; 
            margin: 8px 0 5px 0;
            border-radius: 3px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 8px;
        }
        td, th { 
            padding: 3px 5px; 
            vertical-align: top;
            border: 1px solid #ddd;
        }
        .label { width: 60%; }
        .value { width: 40%; text-align: right; }
        .total-row td { 
            font-weight: bold; 
            border-top: 2px solid #000; 
            padding-top: 5px;
            background-color: #f8f9fa;
        }
        .highlight { 
            font-weight: bold; 
            font-size: 14px; 
            margin: 8px 0;
            color: #28a745;
        }
        .note { 
            font-size: 9px; 
            margin-top: 3px;
            color: #666;
            font-style: italic;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 5px 0;
        }
        .logo-container {
            margin-bottom: 5px;
        }
        .logo-container img {
            max-height: 50px;
        }
        .periode-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

    <!-- Header Perusahaan -->
    <div class="text-center">
        @if(!empty($setting['logo']))
           <div class="logo-container">
               <img src="storage/{{$setting['logo']}}" alt="Logo" width="60">
           </div>
        @endif
        <div class="company-name">{{ $setting['company_name'] }}</div>
        <div class="periode-info">
            Slip Bonus - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
        </div>
    </div>

    <!-- Informasi Pegawai -->
    <table style="border: none; margin-bottom: 10px;">
        <tr>
            <td style="border: none; padding: 2px 0;"><strong>NIK:</strong></td>
            <td style="border: none; padding: 2px 0;">{{ $user->nik }}</td>
        </tr>
        <tr>
            <td style="border: none; padding: 2px 0;"><strong>Nama:</strong></td>
            <td style="border: none; padding: 2px 0;">{{ $user->name }}</td>
        </tr>
        <tr>
            <td style="border: none; padding: 2px 0;"><strong>NIP:</strong></td>
            <td style="border: none; padding: 2px 0;">{{ $user->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td style="border: none; padding: 2px 0;"><strong>Jabatan:</strong></td>
            <td style="border: none; padding: 2px 0;">{{ $user->jabatan ?? '-' }}</td>
        </tr>
    </table>

    <!-- Detail Bonus -->
    <div class="bg-header">Detail Bonus</div>
    <table>
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th width="5%" align="center">No</th>
                <th width="65%">Keterangan</th>
                <th width="30%" align="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bonuses as $index => $bonus)
            <tr>
                <td align="center">{{ $index + 1 }}</td>
                <td>{{ $bonus->keterangan }}</td>
                <td align="right">Rp {{ number_format($bonus->nominal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td colspan="2" align="right"><strong>TOTAL BONUS</strong></td>
                <td align="right">
                    <strong>Rp {{ number_format($totalBonus, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Informasi Tambahan -->
    <div class="text-center">
        <div class="highlight">Total Bonus</div>
        <div class="highlight">Rp {{ number_format($totalBonus, 0, ',', '.') }}</div>
        <div class="note">
            Bonus akan ditambahkan ke gaji bulan berikutnya<br>
            Slip ini dicetak secara otomatis, simpan untuk arsip.
        </div>
    </div>

    <!-- Footer -->
    <div class="text-right text-muted" style="margin-top: 15px;">
        <small>
            {{ $setting['lokasi'] }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}
        </small>
    </div>

</body>
</html>