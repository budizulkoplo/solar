@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="/mobile/home" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Slip Bonus</div>
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

    <h6 class="mb-3"><ion-icon name="gift-outline"></ion-icon> Daftar Bonus Tahun {{ $tahun }}</h6>

    @if($data->count())
        <div class="listview">
            @foreach ($data as $item)
                @php
                    try {
                        $bulan = \Carbon\Carbon::createFromFormat('Y-m', $item->periode)->month;
                        $tahunItem = \Carbon\Carbon::createFromFormat('Y-m', $item->periode)->year;
                        $bulanText = \Carbon\Carbon::createFromDate($tahunItem, $bulan, 1)->locale('id')->isoFormat('MMMM');
                    } catch (\Exception $e) {
                        $bulanText = 'Invalid Date';
                        $tahunItem = $tahun;
                    }
                @endphp
                <a href="{{ route('mobile.bonus.detail', $item->periode) }}" class="card mb-2" style="background-color: #fff3cd;">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 text-success">Bonus - {{ $bulanText }} {{ $tahunItem }}</h5>
                                <small class="text-muted">
                                    <ion-icon name="list-outline"></ion-icon> {{ $item->jumlah_bonus }} item bonus
                                </small>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1 text-success">Rp {{ number_format($item->total_nominal) }}</h6>
                                <small class="text-muted">
                                    @if($item->last_created)
                                        <ion-icon name="calendar-outline"></ion-icon> 
                                        {{ \Carbon\Carbon::parse($item->last_created)->format('d/m/Y') }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="mt-2">
                            @php
                                // Gunakan namespace lengkap untuk model Bonus
                                $latestBonus = \App\Models\Bonus::where('periode', $item->periode)
                                    ->where('nik', Auth::user()->nik)
                                    ->orderBy('created_at', 'desc')
                                    ->first();
                            @endphp
                            @if($latestBonus)
                                <small class="text-muted">
                                    <ion-icon name="information-circle-outline"></ion-icon>
                                    Terakhir: {{ Str::limit($latestBonus->keterangan, 40) }}
                                </small>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 py-1">
                        <small class="text-primary">
                            <ion-icon name="chevron-forward-outline" class="me-1"></ion-icon>
                            Lihat detail bonus
                        </small>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center">
            <ion-icon name="gift-outline" class="fs-1 mb-2"></ion-icon>
            <p class="mb-0">Tidak ada data bonus untuk tahun ini.</p>
            <small class="text-muted">Bonus akan muncul setelah diinput oleh HRD</small>
        </div>
    @endif
</div>

<style>
    .card {
        border-radius: 10px;
        border: 1px solid #ffc107;
    }
    .card:hover {
        transform: translateY(-2px);
        transition: transform 0.2s;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
    }
</style>
@endsection