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
        @if(!empty($setting['logo']))
            <img src="storage/{{$setting['logo']}}" alt="Logo" style="height:50px;">
        @endif
        <h6 class="mt-2 mb-0">{{ $setting['company_name'] }}</h6>
        <small class="text-muted">Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</small>
    </div>

    <div class="mb-3">
        <strong>NIP:</strong> {{ $user->nip ?? '-' }}<br>
        <strong>Nama:</strong> {{ $rekap->nama ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $user->jabatan ?? '-' }}<br>

    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2 px-3">Pendapatan</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">Gaji Pokok <span>Rp {{ number_format($rekap->gajipokok) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Pek Tambahan <span>Rp {{ number_format($rekap->pek_tambahan) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Masa Kerja <span>Rp {{ number_format($rekap->masakerja) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Komunikasi <span>Rp {{ number_format($rekap->komunikasi) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Transportasi <span>Rp {{ number_format($rekap->transportasi) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Konsumsi <span>Rp {{ number_format($rekap->konsumsi) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Tunj Asuransi <span>Rp {{ number_format($rekap->tunj_asuransi) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Jabatan <span>Rp {{ number_format($rekap->jabatan) }}</span></li>
            <li class="list-group-item fw-bold d-flex justify-content-between text-primary">
                Total Pendapatan <span>Rp {{ number_format($totalPendapatan) }}</span>
            </li>
        </ul>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-danger text-white py-2 px-3">Potongan</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">Cicilan <span>Rp {{ number_format($rekap->cicilan) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Asuransi <span>Rp {{ number_format($rekap->asuransi) }}</span></li>
            <li class="list-group-item d-flex justify-content-between">Zakat <span>Rp {{ number_format($rekap->zakat) }}</span></li>
            <li class="list-group-item fw-bold d-flex justify-content-between text-danger">
                Total Potongan <span>Rp {{ number_format($totalPotongan) }}</span>
            </li>
        </ul>
    </div>

    <div class="card border-success text-center">
        <div class="card-body">
            <h5 class="text-success">Total Diterima</h5>
            <h4 class="text-success">Rp {{ number_format($jumlah) }}</h4>
        </div>
    </div>

    <div class="text-end text-muted mt-3">
        <small>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

    <a href="{{ route('mobile.payroll.download', $rekap->id) }}" class="btn btn-outline-primary mt-3 w-100">
        <ion-icon name="download-outline"></ion-icon> Download Slip
    </a>
</div>
@endsection
