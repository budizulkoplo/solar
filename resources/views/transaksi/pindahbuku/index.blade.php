{{-- resources/views/transaksi/pindahbuku/index.blade.php --}}
<x-app-layout>
    <x-slot name="pagetitle">Transaksi Pindah Buku - {{ session('active_company_name') }}</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Pindah Buku Antar Rekening</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahPindahBuku">
                            <i class="bi bi-transfer"></i> Transaksi Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPindahBuku" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Transaksi</th>
                                <th>Rekening Asal</th>
                                <th>Rekening Tujuan</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pindah Buku -->
    <div class="modal fade" id="modalPindahBuku" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmPindahBuku">
                    @csrf
                    <input type="hidden" name="id" id="idPindahBuku">
                    
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalPindahBukuTitle">Form Pindah Buku</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            {{-- Tanggal --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPindahBuku" required>
                            </div>

                            {{-- Rekening Asal --}}
                            <div class="col-md-8">
                                <label class="form-label">Rekening Asal *</label>
                                <select class="form-select form-select-sm select2" name="rekening_asal_id" id="rekeningAsal" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening Asal --</option>
                                    @foreach(\App\Models\Rekening::where('idcompany', session('active_company_id'))->whereNull('idproject')->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} 
                                            (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Rekening Tujuan --}}
                            <div class="col-md-8">
                                <label class="form-label">Rekening Tujuan *</label>
                                <select class="form-select form-select-sm select2" name="rekening_tujuan_id" id="rekeningTujuan" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening Tujuan --</option>
                                    @foreach(\App\Models\Rekening::where('idcompany', session('active_company_id'))->whereNull('idproject')->get() as $rek)
                                        <option value="{{ $rek->idrek }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Nominal --}}
                            <div class="col-md-4">
                                <label class="form-label">Nominal *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control form-control-sm text-end" 
                                           name="nominal" id="nominal" min="1" required>
                                </div>
                            </div>

                            {{-- Keterangan --}}
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control form-control-sm" name="keterangan" id="keterangan" rows="2" 
                                          placeholder="Keterangan transfer..."></textarea>
                            </div>

                            {{-- Informasi Saldo --}}
                            <div class="col-12">
                                <div class="alert alert-info p-2 mt-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="d-block">
                                                <i class="bi bi-wallet2"></i> 
                                                <strong>Saldo Asal:</strong> 
                                                <span id="saldoAsalDisplay">Rp 0</span>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="d-block">
                                                <i class="bi bi-wallet2"></i> 
                                                <strong>Saldo Tujuan:</strong> 
                                                <span id="saldoTujuanDisplay">Rp 0</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Warning jika saldo tidak cukup --}}
                            <div class="col-12">
                                <div id="saldoWarning" class="alert alert-warning mt-2 p-2" style="display:none;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> 
                                    <strong>Peringatan:</strong> Nominal transfer melebihi saldo rekening asal!
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View Pindah Buku -->
    <div class="modal fade" id="modalViewPindahBuku" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Pindah Buku</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Kode Transaksi</th>
                                    <td id="viewKodeTransaksi">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td id="viewTanggal">-</td>
                                </tr>
                                <tr>
                                    <th>Rekening Asal</th>
                                    <td id="viewRekeningAsal">-</td>
                                </tr>
                                <tr>
                                    <th>Rekening Tujuan</th>
                                    <td id="viewRekeningTujuan">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Nominal</th>
                                    <td id="viewNominal">-</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="viewStatus">-</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td id="viewUser">-</td>
                                </tr>
                                <tr>
                                    <th>Dibuat</th>
                                    <td id="viewCreatedAt">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Keterangan:</strong>
                        <div id="viewKeterangan" class="border p-2 rounded bg-light">
                            -
                        </div>
                    </div>

                    <h6>Riwayat Perubahan</h6>
                    <div id="viewLogContainer" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>
                    </div>
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
            // DataTable untuk Pindah Buku
            let tbPindahBuku = $('#tbPindahBuku').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.pindahbuku.getdata') }}",
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { data: 'kode_transaksi', name: 'kode_transaksi' },
                    { data: 'rekening_asal', name: 'rekeningAsal.namarek' },
                    { data: 'rekening_tujuan', name: 'rekeningTujuan.namarek' },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal',
                        className: 'text-center'
                    },
                    { 
                        data: 'nominal', 
                        name: 'nominal',
                        className: 'text-end'
                    },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center'
                    },
                    { 
                        data: 'user', 
                        name: 'creator.name',
                        className: 'text-center'
                    },
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                columnDefs: [
                    { width: "5%", targets: 0 },
                    { width: "15%", targets: 1 },
                    { width: "20%", targets: 2 },
                    { width: "20%", targets: 3 },
                    { width: "8%", targets: 4 },
                    { width: "12%", targets: 5 },
                    { width: "8%", targets: 6 },
                    { width: "10%", targets: 7 },
                    { width: "8%", targets: 8 }
                ]
            });

            // Set tanggal default ke hari ini
            function setDefaultDate() {
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalPindahBuku').val(today);
            }

            // Format angka ke Rupiah
            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
            }

            // Parse nilai dari format Rupiah
            function parseNumber(value) {
                if (!value) return 0;
                value = value.toString().replace(/[^\d.-]/g, '');
                let num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            }

            // Reset form ke kondisi default
            function resetForm() {
                $('#frmPindahBuku')[0].reset();
                $('#idPindahBuku').val('');
                $('#saldoWarning').hide();
                $('.select2').val(null).trigger('change');
                $('#modalPindahBukuTitle').text('Form Pindah Buku');
                $('#saldoAsalDisplay').text('Rp 0');
                $('#saldoTujuanDisplay').text('Rp 0');
                setDefaultDate();
            }

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalPindahBuku'),
                    width: '100%'
                });
            }

            // Ambil saldo rekening
            let currentSaldoAsal = 0;
            let currentSaldoTujuan = 0;

            // Update saldo rekening asal
            $('#rekeningAsal').change(function() {
                let id = $(this).val();
                if (id) {
                    let url = "{{ route('transaksi.pindahbuku.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        currentSaldoAsal = res.saldo || 0;
                        $('#saldoAsalDisplay').text(formatRupiah(currentSaldoAsal));
                        checkSaldoCukup();
                    });
                } else {
                    currentSaldoAsal = 0;
                    $('#saldoAsalDisplay').text('Rp 0');
                }
            });

            // Update saldo rekening tujuan
            $('#rekeningTujuan').change(function() {
                let id = $(this).val();
                if (id) {
                    let url = "{{ route('transaksi.pindahbuku.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        currentSaldoTujuan = res.saldo || 0;
                        $('#saldoTujuanDisplay').text(formatRupiah(currentSaldoTujuan));
                    });
                } else {
                    currentSaldoTujuan = 0;
                    $('#saldoTujuanDisplay').text('Rp 0');
                }
            });

            // Cek apakah saldo cukup
            function checkSaldoCukup() {
                let nominal = parseNumber($('#nominal').val());
                
                if (currentSaldoAsal > 0 && nominal > 0) {
                    if (nominal > currentSaldoAsal) {
                        $('#saldoWarning').show();
                    } else {
                        $('#saldoWarning').hide();
                    }
                } else {
                    $('#saldoWarning').hide();
                }
            }

            // Event listener untuk input nominal
            $(document).on('input', '#nominal', function() {
                checkSaldoCukup();
            });

            // Tombol tambah transaksi - reset form dan buka modal
            $('#btnTambahPindahBuku').click(function() {
                resetForm();
                $('#modalPindahBuku').modal('show');
            });

            // View transaksi pindah buku
            $(document).on('click', '.view-btn', function() {
                let transaksiId = $(this).data('id');
                
                $.get("{{ route('transaksi.pindahbuku.show', ['id' => ':id']) }}".replace(':id', transaksiId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        
                        // Isi data
                        $('#viewKodeTransaksi').text(data.kode_transaksi);
                        $('#viewTanggal').text(new Date(data.tanggal).toLocaleDateString('id-ID'));
                        $('#viewRekeningAsal').text(data.rekening_asal ? 
                            data.rekening_asal.norek + ' - ' + data.rekening_asal.namarek : '-');
                        $('#viewRekeningTujuan').text(data.rekening_tujuan ? 
                            data.rekening_tujuan.norek + ' - ' + data.rekening_tujuan.namarek : '-');
                        $('#viewNominal').text(formatRupiah(data.nominal));
                        $('#viewStatus').html(getStatusBadge(data.status));
                        $('#viewUser').text(data.creator ? data.creator.name : '-');
                        $('#viewCreatedAt').text(new Date(data.created_at).toLocaleString('id-ID'));
                        $('#viewKeterangan').text(data.keterangan || '-');
                        
                        // Load logs
                        if (data.logs && data.logs.length > 0) {
                            let logHtml = '';
                            data.logs.forEach(function(log) {
                                let waktu = new Date(log.created_at).toLocaleString('id-ID');
                                logHtml += `
                                    <div class="mb-2 pb-2 border-bottom">
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-clock"></i> ${waktu} - ${log.user ? log.user.name : 'System'}
                                        </small>
                                        <p class="mb-0 small">${log.description}</p>
                                    </div>
                                `;
                            });
                            $('#viewLogContainer').html(logHtml);
                        } else {
                            $('#viewLogContainer').html('<p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>');
                        }
                        
                        $('#modalViewPindahBuku').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Edit transaksi pindah buku
            $(document).on('click', '.edit-btn', function() {
                let transaksiId = $(this).data('id');
                
                $.get("{{ route('transaksi.pindahbuku.edit', ['id' => ':id']) }}".replace(':id', transaksiId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        
                        // Isi form dengan data existing
                        resetForm();
                        $('#idPindahBuku').val(data.id);
                        $('#tanggalPindahBuku').val(data.tanggal);
                        $('#rekeningAsal').val(data.rekening_asal_id).trigger('change');
                        $('#rekeningTujuan').val(data.rekening_tujuan_id).trigger('change');
                        $('#nominal').val(data.nominal);
                        $('#keterangan').val(data.keterangan);
                        
                        // Update modal title
                        $('#modalPindahBukuTitle').text('Edit Pindah Buku');
                        
                        // Tampilkan modal
                        $('#modalPindahBuku').modal('show');
                        
                        // Initialize select2 setelah modal ditampilkan
                        setTimeout(() => {
                            initializeSelect2();
                        }, 300);
                        
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Delete transaksi pindah buku
            $(document).on('click', '.delete-btn', function() {
                let transaksiId = $(this).data('id');
                
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Transaksi pindah buku ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pindahbuku.destroy', ['id' => ':id']) }}".replace(':id', transaksiId),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPindahBuku.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
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

            // Submit form pindah buku
            $('#frmPindahBuku').submit(function(e) {
                e.preventDefault();
                processFormSubmission(this);
            });

            function processFormSubmission(formElement) {
                // Validasi rekening asal dan tujuan berbeda
                let rekeningAsal = $('#rekeningAsal').val();
                let rekeningTujuan = $('#rekeningTujuan').val();
                
                if (rekeningAsal === rekeningTujuan) {
                    Swal.fire('Peringatan', 'Rekening asal dan tujuan tidak boleh sama', 'warning');
                    return;
                }

                // Validasi nominal
                let nominal = parseNumber($('#nominal').val());
                if (nominal <= 0) {
                    Swal.fire('Peringatan', 'Nominal harus lebih dari 0', 'warning');
                    return;
                }

                // Cek saldo
                if (currentSaldoAsal > 0 && nominal > currentSaldoAsal) {
                    Swal.fire({
                        title: 'Saldo Tidak Cukup',
                        text: 'Saldo rekening asal tidak mencukupi untuk transaksi ini. Lanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitFormData(formElement);
                        }
                    });
                } else {
                    submitFormData(formElement);
                }
            }

            function submitFormData(formElement) {
                let transaksiId = $('#idPindahBuku').val();
                let url = transaksiId ? 
                    "{{ route('transaksi.pindahbuku.update', ['id' => ':id']) }}".replace(':id', transaksiId) : 
                    "{{ route('transaksi.pindahbuku.store') }}";
                
                let formData = new FormData(formElement);
                if (transaksiId) {
                    formData.append('_method', 'PUT');
                }

                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPindahBuku').modal('hide');
                            tbPindahBuku.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errors = xhr.responseJSON?.errors;
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                        
                        if (errors) {
                            errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                        }
                        
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }

            // Helper function untuk status badge
            function getStatusBadge(status) {
                const badge = {
                    'pending': 'bg-warning',
                    'completed': 'bg-success',
                    'failed': 'bg-danger'
                };
                return `<span class="badge ${badge[status]}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
            }

            // Initialize
            initializeSelect2();
            setDefaultDate();
        });
        </script>
    </x-slot>
</x-app-layout>