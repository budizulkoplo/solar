@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Slip Gaji</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">
    <div class="text-center mb-3">
        @if(!empty($setting->path_logo))
            <img src="{{ asset($setting->path_logo) }}" alt="Logo" style="height:50px;">
        @endif
        <h6 class="mt-2 mb-0">{{ $setting->nama_perusahaan }}</h6>
        <small class="text-muted">Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</small>
    </div>

    <div class="mb-3">
        <strong>NIP:</strong> {{ $user->nip ?? '-' }}<br>
        <strong>Nama:</strong> {{ $pegawai->nama ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $user->jabatan ?? '-' }}<br>
        <strong>Penempatan:</strong> {{ $unitkerja->namaunit ?? '-' }}
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2 px-3">Pendapatan</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">Gaji Pokok <span>Rp {{ number_format($rekap->gaji) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Tunjangan <span>Rp {{ number_format($rekap->tunjangan) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Lembur <span>Rp {{ number_format($rekap->nominallembur) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">HLN <span>Rp {{ number_format($rekap->hln) }}</span></li>
            <li class="list-group-item fw-bold d-flex justify-content-between text-primary">
                Total <span>Rp {{ number_format($rekap->gaji + $rekap->tunjangan + $rekap->nominallembur + $rekap->hln) }}</span>
            </li>
        </ul>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-danger text-white py-2 px-3">Potongan</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">BPJS Kes <span>Rp {{ number_format($rekap->bpjs_kes) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">BPJS TK <span>Rp {{ number_format($rekap->bpjs_tk) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Kasbon <span>Rp {{ number_format($rekap->kasbon) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Sisa Kasbon <span>Rp {{ number_format($rekap->sisakasbon) }}</span></li>
            <li class="list-group-item fw-bold d-flex justify-content-between text-danger">
                Total Potongan <span>Rp {{ number_format($rekap->bpjs_kes + $rekap->bpjs_tk + $rekap->kasbon + $rekap->sisakasbon) }}</span>
            </li>
        </ul>
    </div>

    <div class="card border-success text-center">
        <div class="card-body">
            <h5 class="text-success">Total Diterima</h5>
            <h4 class="text-success">Rp {{ number_format(($rekap->gaji + $rekap->tunjangan + $rekap->nominallembur + $rekap->hln) - ($rekap->bpjs_kes + $rekap->bpjs_tk + $rekap->kasbon + $rekap->sisakasbon)) }}</h4>
        </div>
    </div>

    <div class="text-end text-muted mt-3">
        <small>Semarang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

    <a href="{{ route('hris.payroll.slip', $rekap->id) }}" class="btn btn-outline-primary mt-3 w-100">
        <ion-icon name="download-outline"></ion-icon> Download Slip
    </a>
</div>
@endsection
