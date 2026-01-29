<x-app-layout>
    <x-slot name="pagetitle">Edit Pencairan Bank</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil text-warning me-2"></i>
                        Edit Pencairan Bank
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('pencairan-bank.index') }}">Pencairan Bank</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('pencairan-bank.detail', $pencairan->penjualan_id) }}">Detail</a>
                            </li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('pencairan-bank.detail', $pencairan->penjualan_id) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Form Edit Pencairan</h5>
                        </div>
                        <div class="card-body">
                            <form id="formEditPencairan" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Pencairan *</label>
                                        <select class="form-select" name="jenis_pencairan" required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="dp_awal" {{ $pencairan->jenis_pencairan == 'dp_awal' ? 'selected' : '' }}>DP Awal</option>
                                            <option value="termin_1" {{ $pencairan->jenis_pencairan == 'termin_1' ? 'selected' : '' }}>Termin 1</option>
                                            <option value="termin_2" {{ $pencairan->jenis_pencairan == 'termin_2' ? 'selected' : '' }}>Termin 2</option>
                                            <option value="termin_3" {{ $pencairan->jenis_pencairan == 'termin_3' ? 'selected' : '' }}>Termin 3</option>
                                            <option value="lunas" {{ $pencairan->jenis_pencairan == 'lunas' ? 'selected' : '' }}>Pelunasan</option>
                                            <option value="lainnya" {{ $pencairan->jenis_pencairan == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Termin Ke (Opsional)</label>
                                        <input type="number" class="form-control" name="termin_ke" 
                                               min="1" value="{{ $pencairan->termin_ke }}" 
                                               placeholder="Masukkan termin ke-">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Pencairan *</label>
                                        <input type="date" class="form-control" name="tanggal_pencairan" 
                                               value="{{ \Carbon\Carbon::parse($pencairan->tanggal_pencairan)->format('Y-m-d') }}" 
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nominal Pencairan *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control numeric-input" 
                                                   name="nominal_pencairan" 
                                                   value="{{ number_format($pencairan->nominal_pencairan, 0, ',', '.') }}" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bank Kredit *</label>
                                        <input type="text" class="form-control" name="bank_kredit" 
                                               value="{{ $pencairan->bank_kredit }}" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No. Rekening Bank (Opsional)</label>
                                        <input type="text" class="form-control" name="no_rekening_bank" 
                                               value="{{ $pencairan->no_rekening_bank }}" 
                                               placeholder="Masukkan nomor rekening">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Rekening (Opsional)</label>
                                        <input type="text" class="form-control" name="nama_rekening" 
                                               value="{{ $pencairan->nama_rekening }}" 
                                               placeholder="Masukkan nama rekening">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Keterangan (Opsional)</label>
                                        <textarea class="form-control" name="keterangan" rows="3" 
                                                  placeholder="Tambahkan keterangan...">{{ $pencairan->keterangan }}</textarea>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Bukti Pencairan (Opsional)</label>
                                        <input type="file" class="form-control" name="bukti_pencairan" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">
                                            @if($pencairan->bukti_pencairan)
                                                File saat ini: {{ $pencairan->bukti_pencairan }}
                                            @else
                                                Belum ada file
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    Hanya pencairan dengan status <strong>Pending</strong> yang dapat diedit.
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="window.history.back()">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Pencairan</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Kode</th>
                                    <td>{{ $pencairan->kode_pencairan }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-warning">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $pencairan->penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $pencairan->penjualan->unitDetail->customer->nama_lengkap ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Dibuat Oleh</th>
                                    <td>{{ $pencairan->creator->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Dibuat Pada</th>
                                    <td>{{ $pencairan->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            // Format numeric input
            $('.numeric-input').on('input', function() {
                let value = $(this).val().replace(/[^0-9]/g, '');
                if (value) {
                    value = parseInt(value).toLocaleString('id-ID');
                    $(this).val(value);
                }
            });
            
            // Handle form submission
            $('#formEditPencairan').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Convert nominal back to number
                const nominal = formData.get('nominal_pencairan').replace(/\./g, '');
                formData.set('nominal_pencairan', nominal);
                
                // Show loading
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memproses...');
                
                $.ajax({
                    url: "{{ route('pencairan-bank.update', $pencairan->id) }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan saat menyimpan data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>