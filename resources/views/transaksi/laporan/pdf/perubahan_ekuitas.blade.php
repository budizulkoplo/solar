<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Perubahan Ekuitas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h2, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; vertical-align: middle; }
        th { background: #e9ecef; font-weight: bold; text-align: center; }
        .closing { background: #fff3cd; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Laporan Perubahan Ekuitas</h2>
    <p>Periode: {{ $startDate }} s/d {{ $endDate }} | Module: {{ $module === 'company' ? 'PT/Company' : 'Project' }}</p>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th width="20%">Modal Disetor</th>
                <th width="20%">Laba Ditahan</th>
                <th width="20%">Total Ekuitas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['data']['rows'] as $row)
                <tr class="{{ empty($row['editable']) ? 'closing' : '' }}">
                    <td>{{ $row['keterangan'] }}</td>
                    <td class="text-right">Rp {{ number_format($row['modal_disetor_raw'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($row['laba_ditahan_raw'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($row['total_ekuitas_raw'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
