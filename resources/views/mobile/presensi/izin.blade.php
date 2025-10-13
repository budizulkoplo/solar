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
                    // Warna background & ikon
                    switch($d->status) {
                        case 's':
                            $bgColor = '#e8f1ff'; // biru muda lembut
                            $icon = 'bandage-outline';
                            break;
                        case 'i':
                            $bgColor = '#fde8ec'; // merah muda lembut
                            $icon = 'document-text-outline';
                            break;
                        case 'c':
                            $bgColor = '#fff7e6'; // kuning lembut
                            $icon = 'calendar-outline';
                            break;
                        default:
                            $bgColor = '#f8f9fa';
                            $icon = 'alert-circle-outline';
                    }

                    // Badge approval
                    if($d->status_approved == 0){
                        $badge = '<span class="badge bg-warning text-dark px-3 py-1">Waiting</span>';
                    } elseif($d->status_approved == 1){
                        $badge = '<span class="badge bg-success px-3 py-1">Approved</span>';
                    } elseif($d->status_approved == 2){
                        $badge = '<span class="badge bg-danger px-3 py-1">Declined</span>';
                    } else {
                        $badge = '';
                    }

                    // Badge status (Izin/Sakit/Cuti) warna biru — di kiri samping tanggal
                    $statusText = $d->status == 's' ? 'Sakit' : ($d->status == 'i' ? 'Izin' : 'Cuti');
                    $statusBadge = '<span class="badge text-light px-2 py-1 me-2" style="background-color:#0d6efd; font-size:0.7rem;">' . $statusText . '</span>';
                @endphp

                <div class="card mb-3 shadow-sm" style="background-color: {{ $bgColor }}; border-radius: 12px;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 d-flex justify-content-center align-items-center" style="width: 38px;">
                            <ion-icon name="{{ $icon }}" style="font-size: 1.5rem; color:#0f3a7e;"></ion-icon>
                        </div>

                        {{-- Info teks --}}
                        <div class="flex-grow-1" style="line-height: 1.2;">
                            <div class="d-flex align-items-center mb-1">
                                {!! $statusBadge !!}
                                <h6 class="mb-0" style="font-weight: 600; font-size:0.9rem;">
                                    {{ date('d-m-Y', strtotime($d->tgl_izin)) }}
                                </h6>
                            </div>
                            <small class="text-muted" style="font-size:0.8rem;">{{ $d->keterangan }}</small>
                        </div>

                        {{-- Badge approval --}}
                        <div class="flex-shrink-0 ms-2">
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
