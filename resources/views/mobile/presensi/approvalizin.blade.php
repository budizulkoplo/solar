@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Approval Izin/Sakit/Cuti</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 70px">

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    {{-- Filter --}}
<form method="GET" class="mb-3 d-flex flex-wrap gap-2 align-items-end">
    <div style="flex: 1 1 120px;">
        <label class="form-label small mb-0">Status</label>
        <select name="status" class="form-control form-control-sm w-100">
            <option value="">Semua Status</option>
            <option value="i" {{ request('status')=='i'?'selected':'' }}>Izin</option>
            <option value="s" {{ request('status')=='s'?'selected':'' }}>Sakit</option>
            <option value="c" {{ request('status')=='c'?'selected':'' }}>Cuti</option>
        </select>
    </div>

    <div style="flex: 1 1 120px;">
        <label class="form-label small mb-0">Approval</label>
        <select name="status_approved" class="form-control form-control-sm w-100">
            <option value="">Semua Approval</option>
            <option value="0" {{ request('status_approved')==='0'?'selected':'' }}>Pending</option>
            <option value="1" {{ request('status_approved')=='1'?'selected':'' }}>Approved</option>
            <option value="2" {{ request('status_approved')=='2'?'selected':'' }}>Declined</option>
        </select>
    </div>

    <div style="flex: 1 1 140px;">
        <label class="form-label small mb-0">Bulan</label>
        <input type="month" name="bulan" class="form-control form-control-sm w-100" value="{{ request('bulan') }}">
    </div>

    <div style="flex: 0 0 auto;">
        <button type="submit" class="btn btn-primary btn-sm mt-1">Filter</button>
    </div>
</form>


    @if($izinsakit->count())
        @foreach($izinsakit as $d)
            @php
                switch($d->status) {
                    case 's': $bgColor='#e8f1ff'; $icon='bandage-outline'; break;
                    case 'i': $bgColor='#fde8ec'; $icon='document-text-outline'; break;
                    case 'c': $bgColor='#fff7e6'; $icon='calendar-outline'; break;
                    default: $bgColor='#f8f9fa'; $icon='alert-circle-outline';
                }
                $statusText = $d->status=='s'?'Sakit':($d->status=='i'?'Izin':'Cuti');
                $approvalText = match($d->status_approved) {
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Declined',
                    default => ''
                };
            @endphp

            <div class="card mb-3 shadow-sm" style="background-color: {{ $bgColor }}; border-radius:12px;">
                <div class="card-body p-3">

                    {{-- Header --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <ion-icon name="{{ $icon }}" style="font-size:1.5rem; color:#0f3a7e;"></ion-icon>
                        <span class="badge text-light px-2 py-1" style="background-color:#0d6efd; font-size:0.8rem;">{{ $statusText }}</span>
                        <h6 class="mb-0" style="font-weight:600; font-size:0.9rem;">
                            {{ date('d-m-Y', strtotime($d->tgl_izin)) }} - {{ $d->name }} ({{ $d->jabatan }})
                        </h6>
                        <span class="ms-auto badge {{ $d->status_approved==1?'bg-success':($d->status_approved==2?'bg-danger':'bg-warning') }}">{{ $approvalText }}</span>
                    </div>

                    {{-- Keterangan & Detail --}}
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">
                            {{ $d->keterangan }}
                        </small>

                        {{-- Jika Izin, tampilkan jam mulai & selesai --}}
                        @if($d->status == 'i' && ($d->izin_mulai || $d->izin_selesai))
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <ion-icon name="time-outline" style="font-size:1rem; color:#0d6efd;"></ion-icon>
                                <small>
                                    <strong>Jam:</strong>
                                    {{ $d->izin_mulai ? date('H:i', strtotime($d->izin_mulai)) : '-' }}
                                    s/d
                                    {{ $d->izin_selesai ? date('H:i', strtotime($d->izin_selesai)) : '-' }}
                                </small>
                            </div>
                        @endif

                        {{-- Jika Sakit, tampilkan lampiran --}}
                        @if($d->status == 's' && $d->lampiran)
                            <div class="d-flex align-items-center gap-2">
                                <ion-icon name="document-attach-outline" style="font-size:1rem; color:#dc3545;"></ion-icon>
                                <small>
                                    <strong>Lampiran:</strong>
                                    <a href="{{ asset('storage/uploads/lampiranizin/'.$d->lampiran) }}" target="_blank" class="text-primary" style="text-decoration: underline;">
                                        Lihat Surat Sakit
                                    </a>
                                </small>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    @if($d->status_approved == 0)
                        <div class="d-flex gap-2">
                            <form action="{{ url('/mobile/presensi/approvedizin') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id_izinsakit_form" value="{{ $d->id }}">
                                <input type="hidden" name="status_approved" value="1">
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form action="{{ url('/mobile/presensi/approvedizin') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id_izinsakit_form" value="{{ $d->id }}">
                                <input type="hidden" name="status_approved" value="2">
                                <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                            </form>
                            <form action="{{ url('/mobile/presensi/hapusizin/'.$d->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm">Hapus</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{ $izinsakit->links() }}
    @else
        <div class="alert alert-warning text-center">
            Tidak ada pengajuan izin/sakit/cuti.
        </div>
    @endif
</div>
@endsection
