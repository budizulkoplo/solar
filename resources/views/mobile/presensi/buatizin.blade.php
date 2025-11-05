@extends('layouts.mobile')

@section('header')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
<style>
    .datepicker-modal {
        max-height: 430px !important;
    }
    .datepicker-date-display {
        background-color: #0f3a7e !important;
    }
</style>
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Form Izin / Cuti / Sakit</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        <form method="POST" action="/mobile/presensi/storeizin" id="frmIzin" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <input type="text" id="tgl_izin" name="tgl_izin" class="form-control datepicker" placeholder="Tanggal">
            </div>

            <div class="form-group">
                <select name="status" id="status" class="form-control">
                    <option value="">Pilih Jenis</option>
                    <option value="i">Izin</option>
                    <option value="s">Sakit</option>
                    <option value="c">Cuti</option>
                </select>
            </div>

            {{-- Input tambahan dinamis --}}
            <div id="izin-fields" style="display:none;">
                <div class="form-group">
                    <label>Jam Mulai Izin</label>
                    <input type="time" name="izin_mulai" id="izin_mulai" class="form-control">
                </div>
                <div class="form-group">
                    <label>Jam Selesai Izin</label>
                    <input type="time" name="izin_selesai" id="izin_selesai" class="form-control">
                </div>
            </div>

            <div id="sakit-fields" style="display:none;">
                <div class="form-group">
                    <label>Lampiran Surat Sakit (Opsional)</label>
                    <input type="file" name="lampiran" id="lampiran" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                </div>
            </div>

            <div class="form-group">
                <textarea name="keterangan" id="keterangan" cols="30" rows="3" class="form-control" placeholder="Keterangan"></textarea>
            </div>

            <div class="form-group">
                <button class="btn btn-primary w-100">Kirim</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('myscript')
<script>
$(document).ready(function() {
    $(".datepicker").datepicker({
        format: "yyyy/mm/dd"    
    });

    // Cek pengajuan duplikat
    $("#tgl_izin").change(function() {
        var tgl_izin = $(this).val();
        $.ajax({
            type: 'POST',
            url: '/mobile/presensi/cekpengajuanizin',
            data: {
                _token: "{{ csrf_token() }}",
                tgl_izin: tgl_izin
            },
            success: function(respond) {
                if (respond == 1) {
                    Swal.fire({
                        title: 'Oopsl !',
                        text: 'Anda sudah mengajukan izin pada tanggal tersebut!',
                        icon: 'warning'
                    }).then(() => $("#tgl_izin").val(""));
                }
            }
        });
    });

    // Tampilkan field tambahan sesuai status
    $('#status').change(function() {
        var val = $(this).val();
        $('#izin-fields').toggle(val === 'i');
        $('#sakit-fields').toggle(val === 's');
    });

    // Validasi sebelum submit
    $('#frmIzin').submit(function() {
        var tgl_izin = $('#tgl_izin').val();
        var status = $('#status').val();
        var keterangan = $('#keterangan').val(); 

        if (tgl_izin == "") {
            Swal.fire('Oops!', 'Tanggal harus diisi', 'warning');
            return false;
        } 
        if (status == "") {
            Swal.fire('Oops!', 'Status harus diisi', 'warning');
            return false;
        }
        if (keterangan == "") {
            Swal.fire('Oops!', 'Keterangan harus diisi', 'warning');
            return false;
        }

        // Validasi khusus izin
        if (status == 'i') {
            if ($('#izin_mulai').val() == "" || $('#izin_selesai').val() == "") {
                Swal.fire('Oops!', 'Jam mulai dan selesai izin harus diisi', 'warning');
                return false;
            }
        }
    });
});
</script>
@endpush
