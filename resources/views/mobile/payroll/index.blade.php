@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="/mobile/home" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Slip Gaji</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">
    <form method="GET" class="mb-3">
        <label for="tahun" class="form-label">Pilih Tahun</label>
        <select name="tahun" id="tahun" class="form-control" onchange="this.form.submit()">
            @foreach ($tahunList as $th)
                <option value="{{ $th }}" {{ $th == $tahun ? 'selected' : '' }}>Tahun {{ $th }}</option>
            @endforeach
        </select>
    </form>

    <h6 class="mb-3"><ion-icon name="calendar-outline"></ion-icon> Daftar Slip Gaji Tahun {{ $tahun }}</h6>

    @if($data->count())
        <div class="listview">
            @foreach ($data as $item)
                @php
                    $bulanText = \Carbon\Carbon::createFromDate($tahun, $item->bulan, 1)->locale('id')->isoFormat('MMMM');
                @endphp
                <a href="{{ route('mobile.payroll.detail', [$tahun, $item->bulan]) }}" class="card mb-2" style="background-color: #e3fcec;">
                    <div class="card-body p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 text-primary">Slip Gaji - {{ $bulanText }}</h5>
                            <small class="text-muted">📅 Periode {{ $bulanText }} {{ $tahun }}</small>
                        </div>
                        <ion-icon name="chevron-forward-outline" class="text-muted" style="font-size: 1.3rem;"></ion-icon>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center">
            Tidak ada data slip gaji untuk tahun ini.
        </div>
    @endif
</div>
@endsection
