@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="{{ route('mobile.home') }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Riwayat Transaksi Armada</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">
    {{-- Filter Tanggal --}}
    <!-- Form Pilih Tanggal -->
    <form method="GET" action="{{ route('mobile.transaksi_armada.history') }}" class="mb-2">
        <label for="tgl" class="form-label small">Pilih Tanggal</label>
        <input type="date" name="tgl" id="tgl"
            value="{{ $tanggal ?? request('tgl', \Carbon\Carbon::today()->toDateString()) }}"
            class="form-control form-control-sm"
            onchange="this.form.submit()">
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-sm text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Plat</th>
                    <th>Jam</th>
                    <th>Vol (m³)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transaksis as $t)
                <tr>
                    <td>{{ $t->no_struk }}</td>
                    <td>{{ $t->armada->nopol ?? '-' }}</td>
                    <td title="{{ \Carbon\Carbon::parse($t->tgl_transaksi)->format('d/m/Y H:i') }}">
                        {{ \Carbon\Carbon::parse($t->tgl_transaksi)->format('H:i') }}
                    </td>
                    <td>{{ number_format($t->volume / 1000000, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('mobile.transaksi_armada.print', $t->id) }}" 
                        class="btn btn-success btn-sm p-1 px-2">
                        <ion-icon name="print-outline"></ion-icon>
                        </a>
                        @if(\Carbon\Carbon::parse($t->tgl_transaksi)->isToday())
                            <a href="{{ route('mobile.transaksi_armada.edit', $t->id) }}" 
                            class="btn btn-primary btn-sm p-1 px-2">
                            <ion-icon name="create-outline"></ion-icon>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada transaksi</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $transaksis->withQueryString()->links() }}
</div>

@endsection
