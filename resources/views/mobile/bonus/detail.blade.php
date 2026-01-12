@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Detail Bonus</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">
    <div class="text-center mb-3">
        @if(!empty($setting['logo']))
           <img src="/storage/{{$setting['logo']}}" alt="Logo" width="75"><br>
        @endif
        <h6 class="mt-2 mb-0">{{ $setting['company_name'] }}</h6>
        <small class="text-muted">
            Detail Bonus - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
        </small>
    </div>

    <div class="mb-3">
        <strong>NIK:</strong> {{ $user->nik }}<br>
        <strong>Nama:</strong> {{ $user->name }}<br>
        <strong>NIP:</strong> {{ $user->nip ?? '-' }}<br>
        <strong>Jabatan:</strong> {{ $user->jabatan ?? '-' }}<br>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-warning text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <span>Daftar Bonus</span>
            <span class="badge bg-light text-warning">{{ $bonuses->count() }} item</span>
        </div>
        <ul class="list-group list-group-flush">
            @foreach($bonuses as $index => $bonus)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold mb-1">{{ $index + 1 }}. {{ $bonus->keterangan }}</div>
                            <small class="text-muted">
                                <ion-icon name="time-outline"></ion-icon>
                                {{ \Carbon\Carbon::parse($bonus->created_at)->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 text-warning">Rp {{ number_format($bonus->nominal) }}</h6>
                        </div>
                    </div>
                </li>
            @endforeach
        </