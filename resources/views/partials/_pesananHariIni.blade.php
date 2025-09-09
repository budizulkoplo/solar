@forelse($pesananTerbaru as $index => $p)
<tr>
    <td>{{ $index+1 }}</td>
    <td>{{ $p->nomor_invoice ?? '-' }}</td>
    <td>{{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}</td>
    <td>{{ $p->customer ?? '-' }}</td>
    <td>{{ number_format($p->grandtotal,0,',','.') }}</td>
    <td>
        <span class="badge bg-{{ $p->status_ambil == 'finish' ? 'success' : 'warning' }}">
            {{ ucfirst($p->status_ambil ?? '-') }}
        </span>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center">Belum ada pesanan</td>
</tr>
@endforelse
