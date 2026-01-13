@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Daftar Agenda</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
@php
    $hariSenin = \Carbon\Carbon::now()->startOfWeek()->addWeek()->format('d M Y');
@endphp

<div class="p-3" style="margin-top: 40px">
    <form method="GET" class="mb-3">
        <div class="form-group mb-2">
            <input type="month" id="bulan" name="bulan" class="form-control" value="{{ $bulan }}">
        </div>
        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
            <ion-icon name="document-text-outline" style="font-size: 1.2rem"></ion-icon>
            <span>Tampilkan</span>
        </button>
    </form>

    @if($agenda->count())
        <div class="listview">
            

            {{-- Agenda dari database --}}
            @foreach($agenda as $item)
                @php
                    $carbonDate = \Carbon\Carbon::parse($item->tgl . ' ' . $item->waktu);
                    $isPast = $carbonDate->isPast();
                    $bgColor = $isPast ? '#ffe6ec' : '#e3fcec';
                    $status = $isPast ? 'Berakhir' : 'Akan Datang';
                    $badgeClass = $isPast ? 'bg-danger' : 'bg-success';
                @endphp
                <div class="card mb-2" style="background-color: {{ $bgColor }};">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="mb-1 text-warning">{{ $item->namaagenda }}</h5>
                                <small class="text-muted d-block">
                                    📅 {{ \Carbon\Carbon::parse($item->tgl)->translatedFormat('d F Y') }}<br>
                                    🕘 {{ substr($item->waktu, 0, 5) }}<br>
                                    📌 {{ $item->jenis }}<br>
                                    <h6 class="mb-1 text-primary">👤 {{ $item->creator }}</h6>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $badgeClass }} py-1 px-3 rounded-pill mt-1">{{ $status }}</span>
                                <small class="text-muted d-block mt-1">
                                    🏠 <strong>{{ $item->lokasi }}</strong><br>
                                    👥 {{ $item->peserta }}
                                </small>

                                @if(auth()->check() && auth()->user()->nik === $item->nik)
                                    <form action="{{ route('mobile.presensi.agenda.delete', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin ingin menghapus agenda ini?')">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center mt-3">
            Tidak ada data agenda untuk bulan ini.
        </div>
    @endif
</div>
@endsection
