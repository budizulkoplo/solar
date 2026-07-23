<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Neraca</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h2, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px; vertical-align: middle; }
        th { background: #e9ecef; font-weight: bold; text-align: center; }
        .section { background: #cfe2ff; font-weight: bold; }
        .subtotal { background: #f1f3f5; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Laporan Neraca</h2>
    <p>Periode: {{ $startDate }} s/d {{ $endDate }} | Module: {{ $module === 'company' ? 'PT/Company' : 'Project' }}</p>

    <table>
        <thead>
            <tr>
                <th colspan="3">AKTIVA</th>
                <th colspan="3">PASIVA</th>
            </tr>
            <tr>
                <th width="6%">No</th>
                <th>Akun</th>
                <th width="18%">Nilai</th>
                <th width="6%">No</th>
                <th>Akun</th>
                <th width="18%">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @php
                $maxRows = max(count($report['aktiva_rows']), count($report['pasiva_rows']));
            @endphp
            @for($i = 0; $i < $maxRows; $i++)
                @php
                    $aktiva = $report['aktiva_rows'][$i] ?? ['no' => '', 'label' => '', 'value' => null];
                    $pasiva = $report['pasiva_rows'][$i] ?? ['no' => '', 'label' => '', 'value' => null];
                    $aktivaClass = !empty($aktiva['is_header']) ? 'section' : (!empty($aktiva['is_subtotal']) ? 'subtotal' : '');
                    $pasivaClass = !empty($pasiva['is_header']) ? 'section' : (!empty($pasiva['is_subtotal']) ? 'subtotal' : '');
                @endphp
                <tr>
                    <td class="text-center {{ $aktivaClass }}">{{ $aktiva['no'] }}</td>
                    <td class="{{ $aktivaClass }}">{{ $aktiva['label'] }}</td>
                    <td class="text-right {{ $aktivaClass }}">
                        {{ is_null($aktiva['value']) ? '' : 'Rp ' . number_format($aktiva['value'], 0, ',', '.') }}
                    </td>
                    <td class="text-center {{ $pasivaClass }}">{{ $pasiva['no'] }}</td>
                    <td class="{{ $pasivaClass }}">{{ $pasiva['label'] }}</td>
                    <td class="text-right {{ $pasivaClass }}">
                        {{ is_null($pasiva['value']) ? '' : 'Rp ' . number_format($pasiva['value'], 0, ',', '.') }}
                    </td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="subtotal">
                <td colspan="2" class="text-center">Total Aktiva</td>
                <td class="text-right">Rp {{ number_format($report['total_aktiva'], 0, ',', '.') }}</td>
                <td colspan="2" class="text-center">Total Pasiva</td>
                <td class="text-right">Rp {{ number_format($report['total_pasiva'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
