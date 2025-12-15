<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .filter-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .filter-info p {
            margin: 5px 0;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-box {
            flex: 1;
            min-width: 200px;
            margin: 5px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .summary-box h4 {
            margin: 0;
            color: #333;
        }
        .summary-box p {
            margin: 5px 0;
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #343a40;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-cash { background-color: #28a745; color: white; }
        .badge-kredit { background-color: #17a2b8; color: white; }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: right;
            color: #666;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN PENJUALAN</h2>
        <p>Periode: {{ $start_date ? date('d/m/Y', strtotime($start_date)) : '-' }} s/d {{ $end_date ? date('d/m/Y', strtotime($end_date)) : '-' }}</p>
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
    </div>
    
    <div class="filter-info">
        <p><strong>Filter:</strong></p>
        <p>Project: {{ $filter_project }}</p>
        <p>Metode Pembayaran: {{ $filter_metode }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-box">
            <h4>Total Penjualan</h4>
            <p>{{ number_format($total_penjualan) }}</p>
        </div>
        <div class="summary-box">
            <h4>Total Harga Jual</h4>
            <p>Rp {{ number_format($total_harga_jual, 0, ',', '.') }}</p>
        </div>
        <div class="summary-box">
            <h4>Total DP</h4>
            <p>Rp {{ number_format($total_dp, 0, ',', '.') }}</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Penjualan</th>
                <th>Project</th>
                <th>Unit</th>
                <th>Customer</th>
                <th>NIK</th>
                <th>Tanggal Akad</th>
                <th>Harga Jual</th>
                <th>DP Awal</th>
                <th>Metode Bayar</th>
                <th>Info Kredit</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualans as $index => $penjualan)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $penjualan->kode_penjualan }}</td>
                <td>{{ $penjualan->unitDetail->unit->project->namaproject ?? '-' }}</td>
                <td>{{ $penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                <td>{{ $penjualan->customer->nama_lengkap ?? '-' }}</td>
                <td>{{ $penjualan->customer->nik ?? '-' }}</td>
                <td class="text-center">{{ $penjualan->tanggal_akad ? date('d/m/Y', strtotime($penjualan->tanggal_akad)) : '-' }}</td>
                <td class="text-right">Rp {{ number_format($penjualan->harga_jual, 0, ',', '.') }}</td>
                <td class="text-right">{{ $penjualan->dp_awal ? 'Rp ' . number_format($penjualan->dp_awal, 0, ',', '.') : '-' }}</td>
                <td class="text-center">
                    @php
                        $badgeClass = [
                            'cash' => 'badge-cash',
                            'kredit' => 'badge-kredit'
                        ][$penjualan->metode_pembayaran] ?? '';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($penjualan->metode_pembayaran) }}</span>
                </td>
                <td class="text-center">
                    @if($penjualan->metode_pembayaran === 'kredit')
                        {{ $penjualan->bank_kredit }} ({{ $penjualan->tenor_kredit }} bulan)
                    @else
                        -
                    @endif
                </td>
                <td>{{ $penjualan->keterangan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Halaman 1 dari 1 • Sistem Informasi Property</p>
    </div>
</body>
</html>