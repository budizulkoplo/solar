<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bookings</title>
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
        .badge-active { background-color: #28a745; color: white; }
        .badge-canceled { background-color: #dc3545; color: white; }
        .badge-expired { background-color: #ffc107; color: #212529; }
        .badge-completed { background-color: #17a2b8; color: white; }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: right;
            color: #666;
            font-size: 11px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN BOOKINGS</h2>
        <p>Periode: {{ $start_date ? date('d/m/Y', strtotime($start_date)) : '-' }} s/d {{ $end_date ? date('d/m/Y', strtotime($end_date)) : '-' }}</p>
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
    </div>
    
    <div class="filter-info">
        <p><strong>Filter:</strong></p>
        <p>Project: {{ $filter_project }}</p>
        <p>Status: {{ $filter_status }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-box">
            <h4>Total Bookings</h4>
            <p>{{ number_format($total_bookings) }}</p>
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
                <th>Kode Booking</th>
                <th>Project</th>
                <th>Unit</th>
                <th>Customer</th>
                <th>NIK</th>
                <th>No. HP</th>
                <th>Tanggal Booking</th>
                <th>DP Awal</th>
                <th>Metode Bayar</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $index => $booking)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $booking->kode_booking }}</td>
                <td>{{ $booking->unitDetail->unit->project->namaproject ?? '-' }}</td>
                <td>{{ $booking->unitDetail->unit->namaunit ?? '-' }}</td>
                <td>{{ $booking->customer->nama_lengkap ?? '-' }}</td>
                <td>{{ $booking->customer->nik ?? '-' }}</td>
                <td>{{ $booking->customer->no_hp ?? '-' }}</td>
                <td class="text-center">{{ $booking->tanggal_booking ? date('d/m/Y', strtotime($booking->tanggal_booking)) : '-' }}</td>
                <td class="text-right">Rp {{ number_format($booking->dp_awal, 0, ',', '.') }}</td>
                <td>{{ $booking->metode_pembayaran_dp }}</td>
                <td class="text-center">{{ $booking->tanggal_jatuh_tempo ? date('d/m/Y', strtotime($booking->tanggal_jatuh_tempo)) : '-' }}</td>
                <td class="text-center">
                    @php
                        $badgeClass = [
                            'active' => 'badge-active',
                            'canceled' => 'badge-canceled',
                            'expired' => 'badge-expired',
                            'completed' => 'badge-completed'
                        ][$booking->status_booking] ?? '';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($booking->status_booking) }}</span>
                </td>
                <td>{{ $booking->keterangan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Halaman 1 dari 1 • Sistem Informasi Property</p>
    </div>
</body>
</html>