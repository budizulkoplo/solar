<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Detail Nota: {{ $nota->nota_no }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="120">No. Nota</th>
                                    <td>{{ $nota->nota_no }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Vendor</th>
                                    <td>{{ $nota->namavendor ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Project</th>
                                    <td>{{ $nota->namaproject ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="120">Status</th>
                                    <td>
                                        @if($nota->status == 'paid')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($nota->status == 'partial')
                                            <span class="badge bg-warning">Bayar Sebagian</span>
                                        @else
                                            <span class="badge bg-danger">Belum Bayar</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cashflow</th>
                                    <td>
                                        @if($nota->cashflow == 'in')
                                            <span class="badge bg-success">Pemasukan</span>
                                        @else
                                            <span class="badge bg-danger">Pengeluaran</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td class="fw-bold">Rp {{ number_format($nota->total, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Rekening</th>
                                    <td>{{ $nota->namarek ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="mt-4">Detail Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Item</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->nama_item }}</td>
                                    <td class="text-end">{{ $item->jumlah }}</td>
                                    <td class="text-end">Rp {{ number_format($item->harga_item, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($payments->count() > 0)
                    <h6 class="mt-4">History Pembayaran</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Bayar</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Metode</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $key => $payment)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($payment->tgl_bayar)->format('d/m/Y') }}</td>
                                    <td class="text-end">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</td>
                                    <td>{{ $payment->metode ?? '-' }}</td>
                                    <td>{{ $payment->keterangan ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>