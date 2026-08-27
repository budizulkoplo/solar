<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi by Kategori</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h2, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #e9ecef; font-weight: bold; text-align: center; }
        .group { background: #f1f3f5; font-weight: bold; }
        .summary { background: #d1e7dd; font-weight: bold; }
        .text-right { text-align: right; }
        .half { width: 49%; display: inline-block; vertical-align: top; }
    </style>
</head>
<body>
    <h2>Laporan Transaksi by Kategori</h2>
    <p>Periode: {{ $startDate }} s/d {{ $endDate }} | Module: {{ $module === 'company' ? 'PT/Company' : 'Project' }}</p>
    <p>Kategori: {{ $report['summary']['kode_label'] ?? '-' }}</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Transaksi</th>
                <th>Header</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
                <th>Selisih</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($report['data']['summaries'] ?? []) as $item)
                <tr>
                    <td>{{ $item['kode'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td>{{ $item['header'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['total_pemasukan_raw'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item['total_pengeluaran_raw'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item['selisih_raw'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $item['jumlah_transaksi'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data</td>
                </tr>
            @endforelse
            <tr class="summary">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">Rp {{ number_format($report['summary']['total_pemasukan_raw'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($report['summary']['total_pengeluaran_raw'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($report['summary']['selisih_raw'], 0, ',', '.') }}</td>
                <td class="text-right">{{ $report['summary']['jumlah_transaksi'] }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th colspan="4">PEMASUKAN</th>
                <th colspan="4">PENGELUARAN</th>
            </tr>
            <tr>
                <th>Tgl / Nota</th>
                <th>Kode</th>
                <th>Deskripsi</th>
                <th>Nominal</th>
                <th>Tgl / Nota</th>
                <th>Kode</th>
                <th>Deskripsi</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pemasukan = $report['data']['pemasukan'] ?? [];
                $pengeluaran = $report['data']['pengeluaran'] ?? [];
                $max = max(count($pemasukan), count($pengeluaran));
            @endphp
            @if($max === 0)
                <tr>
                    <td colspan="8" style="text-align:center;">Tidak ada detail transaksi</td>
                </tr>
            @else
                @for($i = 0; $i < $max; $i++)
                    @php
                        $in = $pemasukan[$i] ?? null;
                        $out = $pengeluaran[$i] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $in ? $in['tanggal_display'] . ' / ' . $in['nota_no'] : '' }}</td>
                        <td>{{ $in['kode'] ?? '' }}</td>
                        <td>{{ $in['deskripsi'] ?? '' }}</td>
                        <td class="text-right">{{ $in ? 'Rp ' . number_format($in['nominal_raw'], 0, ',', '.') : '' }}</td>
                        <td>{{ $out ? $out['tanggal_display'] . ' / ' . $out['nota_no'] : '' }}</td>
                        <td>{{ $out['kode'] ?? '' }}</td>
                        <td>{{ $out['deskripsi'] ?? '' }}</td>
                        <td class="text-right">{{ $out ? 'Rp ' . number_format($out['nominal_raw'], 0, ',', '.') : '' }}</td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>
</body>
</html>
