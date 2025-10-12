@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Data Izin/Sakit</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">

    <h5 class="mb-3 d-flex align-items-center">
        <ion-icon name="clipboard-outline" class="me-2 text-primary" style="font-size: 1.3rem;"></ion-icon>
        &nbsp;Daftar Izin/Sakit
    </h5>

    @if($dataizin->count())
        <div class="listview">
            @foreach ($dataizin as $d)
                @php
                    // Atur warna dan ikon berdasarkan status
                    switch($d->status) {
                        case 's': // Sakit
                            $bgColor = '#cce5ff'; // biru muda
                            $icon = 'bandage-outline';
                            break;
                        case 'i': // Izin
                            $bgColor = '#f8d7da'; // merah muda
                            $icon = 'document-text-outline';
                            break;
                        case 'c': // Cuti
                            $bgColor = '#fff3cd'; // kuning
                            $icon = 'calendar-outline';
                            break;
                        default:
                            $bgColor = '#f0f0f0';
                            $icon = 'alert-circle-outline';
                    }

                    // Badge approval
                    if($d->status_approved == 0){
                        $badge = '<span class="badge bg-warning text-dark px-3">Waiting</span>';
                    } elseif($d->status_approved == 1){
                        $badge = '<span class="badge bg-success px-3">Approved</span>';
                    } elseif($d->status_approved == 2){
                        $badge = '<span class="badge bg-danger px-3">Declined</span>';
                    } else {
                        $badge = '';
                    }

                    // Badge status (Sakit/Izin/Cuti)
                    $statusBadge = '<span class="badge text-dark px-3" style="background-color: rgba(0,0,0,0.1);">'
                        . ($d->status == 's' ? 'Sakit' : ($d->status == 'i' ? 'Izin' : 'Cuti'))
                        . '</span>';
                @endphp

                <div class="card mb-2 shadow-sm" style="background-color: {{ $bgColor }};">
                    <div class="card-body py-2 d-flex align-items-center gap-3">
                        {{-- Icon --}}
                        <ion-icon name="{{ $icon }}" style="font-size: 1.5rem;"></ion-icon>

                        {{-- Teks dan status --}}
                        <div class="flex-grow-1">
                            <h6 class="mb-1 d-flex align-items-center gap-2">
                                {{ date('d-m-Y', strtotime($d->tgl_izin)) }}
                                {!! $statusBadge !!}
                            </h6>
                            <small class="text-muted">{{ $d->keterangan }}</small>
                        </div>

                        {{-- Badge approval --}}
                        <div class="flex-shrink-0 ms-auto">
                            {!! $badge !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center mt-3">
            Tidak ada data izin atau sakit.
        </div>
    @endif
</div>

{{-- FAB Button --}}
<div class="fab-button bottom-right" style="margin-bottom: 70px">
    <a href="/mobile/presensi/buatizin" class="fab shadow">
        <ion-icon name="add-outline"></ion-icon>
    </a>
</div>
@endsection
