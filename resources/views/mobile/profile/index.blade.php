@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="{{ route('mobile.home') }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Profil Saya</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('mobile.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label>ID</label>
            <input type="text" class="form-control" value="{{ $user->id }}" readonly>
        </div>

        <div class="mb-3">
            <label>NIK</label>
            <input type="text" name="nik" class="form-control" value="{{ old('nik', $user->nik) }}">
        </div>

        <div class="mb-3">
            <label>Nama Lengkap</label>
            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
        </div>

        <div class="mb-3">
            <label>Jabatan</label>
            <input type="text" class="form-control" value="{{ $user->jabatan }}" readonly>
        </div>

        <div class="mb-3">
            <label>No HP</label>
            <input type="text" name="nohp" class="form-control" value="{{ old('nohp', $user->nohp) }}">
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" autocomplete="off">
        </div>

        <div class="mb-3">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control">{{ old('alamat', $user->alamat) }}</textarea>
        </div>

        <div class="mb-3">
            <label>Password (biarkan kosong jika tidak ingin ganti)</label>
            <input type="password" name="password" class="form-control" autocomplete="off">
        </div>

        <div class="mb-3">
            <label>Gaji</label>
            <input type="number" name="gaji" class="form-control" value="{{ old('gaji', $user->gaji) }}">
        </div>

        <div class="mb-3">
            <label>NBM</label>
            <input type="text" name="nbm" class="form-control" value="{{ $user->nbm ?? '' }}">
        </div>

        <div class="mb-3">
            <label>Foto Mobile</label>
            <input type="file" name="fotomobile" class="form-control">
            @if($user->fotomobile)
                <small>Foto lama: {{ $user->fotomobile }}</small>
                <br>
                <img src="{{ Storage::url($user->fotomobile) }}" alt="Foto" class="img-fluid mt-1" style="max-height:100px;">
            @endif
        </div>

        <button class="btn btn-primary w-100" type="submit">Update Profil</button>
    </form>
</div>
@endsection
