<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h2, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; vertical-align: middle; }
        th { background: #e9ecef; font-weight: bold; text-align: center; }
        .group { background: #f1f3f5; font-weight: bold; }
        .subtotal { background: #fff3cd; font-weight: bold; }
        .summary { background: #d1e7dd; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Laporan Laba Rugi</h2>
    <p>Periode: {{ $startDate }} s/d {{ $endDate }} | Module: {{ $module === 'company' ? 'PT/Company' : 'Project' }}</p>

    <table>
        <thead>
            <tr>
                <th width="16%">Kategori</th>
                <th width="14%">Kode Akun</th>
                <th>Nama Akun</th>
                <th width="26%">Rincian</th>
                <th width="18%">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['pendapatan_groups', 'beban_groups'] as $groupKey)
                @foreach(($report['data'][$groupKey] ?? []) as $group)
                    <tr class="group">
                        <td colspan="4">{{ $group['kategori'] }}</td>
                        <td class="text-right">Rp {{ number_format($group['subtotal_raw'], 0, ',', '.') }}</td>
                    </tr>
                    @foreach($group['items'] as $item)
                        <tr>
                            <td></td>
                            <td>{{ $item['kode_akun'] }}</td>
                            <td>{{ $item['nama_akun'] }}</td>
                            <td>{{ $item['rincian'] }}</td>
                            <td class="text-right">Rp {{ number_format($item['nominal_raw'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            <tr class="subtotal">
                <td colspan="4" class="text-right">Total Pendapatan</td>
                <td class="text-right">Rp {{ number_format($report['summary']['total_pendapatan_raw'], 0, ',', '.') }}</td>
            </tr>
            <tr class="subtotal">
                <td colspan="4" class="text-right">Total Beban</td>
                <td class="text-right">Rp {{ number_format($report['summary']['total_beban_raw'], 0, ',', '.') }}</td>
            </tr>
            <tr class="summary">
                <td colspan="4" class="text-right">{{ $report['summary']['status'] }} Bersih</td>
                <td class="text-right">Rp {{ number_format($report['summary']['laba_bersih_raw'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
