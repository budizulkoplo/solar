<x-app-layout>
    <x-slot name="pagetitle">Transaksi - {{ $pekerjaan->nama_pekerjaan }}</x-slot>

    <div class="container-fluid py-2">
        <!-- Header dengan Breadcrumb -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route('construction.transactions.index') }}">Pekerjaan Konstruksi</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $pekerjaan->nama_pekerjaan }}</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">{{ $pekerjaan->nama_pekerjaan }}</h4>
                        <small class="text-muted">{{ $pekerjaan->project->namaproject ?? '-' }}</small>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('construction.transactions.create', $pekerjaan->id) }}" 
                           class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle"></i> Tambah Transaksi
                        </a>
                        <a href="{{ route('construction.transactions.report', $pekerjaan->id) }}" 
                           class="btn btn-sm btn-info" target="_blank">
                            <i class="bi bi-printer"></i> Laporan
                        </a>
                        <a href="{{ route('construction.progress.index') }}" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Pekerjaan -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Anggaran</h6>
                        <h4 class="text-primary">Rp {{ number_format($pekerjaan->anggaran ?? 0, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-info bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title">Total Transaksi</h6>
                        <h4 class="text-info">Rp {{ number_format($totalTransaksi ?? 0, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title">Realisasi</h6>
                        <h4 class="text-success">Rp {{ number_format($realisasiAnggaran ?? 0, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card 
                    @if($sisaAnggaran < 0) bg-danger bg-opacity-10
                    @elseif($sisaAnggaran < $pekerjaan->anggaran * 0.1) bg-warning bg-opacity-10
                    @else bg-success bg-opacity-10 @endif">
                    <div class="card-body">
                        <h6 class="card-title">Sisa Anggaran</h6>
                        <h4 class="@if($sisaAnggaran < 0) text-danger
                            @elseif($sisaAnggaran < $pekerjaan->anggaran * 0.1) text-warning
                            @else text-success @endif">
                            Rp {{ number_format($sisaAnggaran ?? 0, 0, ',', '.') }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Progress Keuangan</span>
                    <span>{{ $pekerjaan->anggaran > 0 ? round(($totalTransaksi / $pekerjaan->anggaran) * 100, 2) : 0 }}%</span>
                </div>
                <div class="progress" style="height: 20px;">
                    @php
                        $percentage = $pekerjaan->anggaran > 0 ? ($totalTransaksi / $pekerjaan->anggaran) * 100 : 0;
                        $progressClass = '';
                        if ($percentage >= 100) {
                            $progressClass = 'bg-danger';
                        } elseif ($percentage >= 80) {
                            $progressClass = 'bg-warning';
                        } else {
                            $progressClass = 'bg-success';
                        }
                    @endphp
                    <div class="progress-bar {{ $progressClass }}" role="progressbar" 
                         style="width: {{ min($percentage, 100) }}%">
                        {{ round($percentage, 1) }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Transaksi -->
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0">Daftar Transaksi</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="transactionsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nota No</th>
                                <th>Nama Transaksi</th>
                                <th>Tanggal</th>
                                <th>Vendor</th>
                                <th class="text-end">Total</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View Nota -->
    <div class="modal fade" id="modalViewNota" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewNotaContent">
                    <!-- Content akan diisi via JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                const pekerjaanId = {{ $pekerjaan->id }};
                
                // Inisialisasi DataTable
                const table = $('#transactionsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("construction.transactions.data", $pekerjaan->id) }}',
                        error: function(xhr) {
                            console.error('DataTable Error:', xhr);
                            Swal.fire('Error', 'Gagal memuat data transaksi', 'error');
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'nota_no', name: 'nota_no' },
                        { data: 'namatransaksi', name: 'namatransaksi' },
                        { 
                            data: 'tanggal', 
                            name: 'tanggal',
                            className: 'text-center',
                            render: function(data) {
                                return data ? new Date(data).toLocaleDateString('id-ID') : '-';
                            }
                        },
                        { 
                            data: 'vendor.namavendor', 
                            name: 'vendor.namavendor',
                            defaultContent: '-'
                        },
                        { 
                            data: 'total', 
                            name: 'total',
                            className: 'text-end',
                            render: function(data) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(data || 0);
                            }
                        },
                        { 
                            data: 'paymen_method', 
                            name: 'paymen_method',
                            className: 'text-center',
                            render: function(data) {
                                return data === 'cash' ? 'Cash' : 'Tempo';
                            }
                        },
                        { 
                            data: 'status', 
                            name: 'status',
                            className: 'text-center'
                        },
                        { 
                            data: 'action', 
                            name: 'action', 
                            orderable: false, 
                            searchable: false,
                            className: 'text-center'
                        }
                    ],
                    order: [[3, 'desc']],
                    language: {
                        emptyTable: "Belum ada transaksi untuk pekerjaan ini",
                        info: "Menampilkan _START_ hingga _END_ dari _TOTAL_ transaksi",
                        infoEmpty: "Menampilkan 0 hingga 0 dari 0 transaksi",
                        infoFiltered: "(disaring dari _MAX_ total transaksi)",
                        lengthMenu: "Tampilkan _MENU_ transaksi",
                        loadingRecords: "Memuat...",
                        processing: "Memproses...",
                        search: "Cari:",
                        zeroRecords: "Tidak ditemukan transaksi yang sesuai"
                    }
                });

                // View nota
                $(document).on('click', '.view-btn', function() {
                    const notaId = $(this).data('id');
                    
                    $.get(`/construction/transactions/${pekerjaanId}/show/${notaId}`, function(res) {
                        if (res.success) {
                            const nota = res.data;
                            
                            let html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">No Nota</th>
                                                <td>${nota.nota_no}</td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal</th>
                                                <td>${new Date(nota.tanggal).toLocaleDateString('id-ID')}</td>
                                            </tr>
                                            <tr>
                                                <th>Nama Transaksi</th>
                                                <td>${nota.namatransaksi}</td>
                                            </tr>
                                            <tr>
                                                <th>Vendor</th>
                                                <td>${nota.vendor ? nota.vendor.namavendor : '-'}</td>
                                            </tr>
                                            <tr>
                                                <th>User</th>
                                                <td>${nota.namauser || '-'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Payment Method</th>
                                                <td>${nota.paymen_method === 'cash' ? 'Cash' : 'Tempo'}</td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Tempo</th>
                                                <td>${nota.tgl_tempo ? new Date(nota.tgl_tempo).toLocaleDateString('id-ID') : '-'}</td>
                                            </tr>
                                            <tr>
                                                <th>Rekening</th>
                                                <td>${nota.rekening ? nota.rekening.norek + ' - ' + nota.rekening.namarek : '-'}</td>
                                            </tr>
                                            <tr>
                                                <th>Total</th>
                                                <td>Rp ${new Intl.NumberFormat('id-ID').format(nota.total || 0)}</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td><span class="badge ${getStatusBadge(nota.status)}">${nota.status.charAt(0).toUpperCase() + nota.status.slice(1)}</span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <h6 class="mt-3">Detail Transaksi</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Kode Transaksi</th>
                                            <th>Deskripsi</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Nominal</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            
                            if (nota.transactions && nota.transactions.length > 0) {
                                nota.transactions.forEach(function(transaction) {
                                    html += `
                                        <tr>
                                            <td>${transaction.kode_transaksi ? transaction.kode_transaksi.kodetransaksi : '-'}</td>
                                            <td>${transaction.description}</td>
                                            <td class="text-center">${transaction.jml}</td>
                                            <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(transaction.nominal || 0)}</td>
                                            <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(transaction.total || 0)}</td>
                                        </tr>
                                    `;
                                });
                            }
                            
                            html += `
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                            <td colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(nota.subtotal || 0)}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>PPN:</strong></td>
                                            <td colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(nota.ppn || 0)}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Diskon:</strong></td>
                                            <td colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(nota.diskon || 0)}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                            <td colspan="2" class="text-end fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(nota.total || 0)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            `;
                            
                            $('#viewNotaContent').html(html);
                            $('#modalViewNota').modal('show');
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Gagal memuat detail transaksi', 'error');
                    });
                });

                // Edit nota
                $(document).on('click', '.edit-btn', function() {
                    const notaId = $(this).data('id');
                    window.location.href = `/construction/transactions/${pekerjaanId}/edit/${notaId}`;
                });

                // Delete nota
                $(document).on('click', '.delete-btn', function() {
                    const notaId = $(this).data('id');
                    
                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Transaksi ini akan dihapus permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/construction/transactions/${pekerjaanId}/delete/${notaId}`,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(res) {
                                    if (res.success) {
                                        table.ajax.reload();
                                        Swal.fire('Berhasil!', res.message, 'success');
                                        
                                        // Refresh halaman untuk update total
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1500);
                                    } else {
                                        Swal.fire('Error!', res.message, 'error');
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                                }
                            });
                        }
                    });
                });

                // Helper function untuk status badge
                function getStatusBadge(status) {
                    const badge = {
                        'open': 'bg-warning',
                        'paid': 'bg-success', 
                        'partial': 'bg-info',
                        'cancel': 'bg-danger'
                    };
                    return badge[status] || 'bg-secondary';
                }
            });
        </script>
    </x-slot>
</x-app-layout>