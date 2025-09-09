@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="{{ route('mobile.transaksi_armada.history') }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Edit Transaksi Armada</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('mobile.transaksi_armada.update', $transaksi->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Plat Nomor (readonly di edit) --}}
        <div class="mb-2">
            <label>Plat Nomor</label>
            <input type="text" class="form-control" value="{{ $transaksi->armada->nopol ?? '-' }}" readonly>
            <input type="hidden" name="armada_id" value="{{ $transaksi->armada_id }}">
        </div>

        {{-- Dimensi (cm) --}}
        <div class="mb-2">
            <label>Panjang (cm)</label>
            <input type="number" name="panjang" class="form-control" value="{{ old('panjang', $transaksi->panjang) }}" required>
        </div>

        <div class="mb-2">
            <label>Lebar (cm)</label>
            <input type="number" name="lebar" class="form-control" value="{{ old('lebar', $transaksi->lebar) }}" required>
        </div>

        <div class="mb-2">
            <label>Tinggi (cm)</label>
            <input type="number" name="tinggi" class="form-control" value="{{ old('tinggi', $transaksi->tinggi) }}" required>
        </div>

        <div class="mb-2">
            <label>Plus (cm)</label>
            <input type="number" name="plus" class="form-control" value="{{ old('plus', $transaksi->plus) }}">
            <input type="hidden" name="project_id" value="{{ $transaksi->project_id }}">
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Update Transaksi</button>
    </form>
</div>
@endsection
