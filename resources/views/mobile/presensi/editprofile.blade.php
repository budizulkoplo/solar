@extends('layouts.mobile')

@section('header')
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Edit Profile</div>
    <div class="right"></div>
</div>
<!-- * App Header -->
@endsection

@section('content')
<div class="row" style="margin-top:4rem">
    <div class="col"></div>

    @php
        $messagesuccess = Session::get('success');
        $messageerror = Session::get('error');
    @endphp

    @if($messagesuccess)
        <div class="alert alert-success w-100">{{ $messagesuccess }}</div>
    @endif

    @if($messageerror)
        <div class="alert alert-danger w-100">{{ $messageerror }}</div>
    @endif
</div>

<form action="/mobile/presensi/{{ $karyawan->nik }}/updateprofile" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="col">

        {{-- NIK --}}
        <div class="form-group boxed">
            <div class="input-wrapper">
                <label for="nik" class="form-label">NIK</label>
                <input type="text" id="nik" class="form-control" value="{{ $karyawan->nik }}" name="nik" readonly>
            </div>
        </div>

        {{-- Nama Lengkap --}}
        <div class="form-group boxed">
            <div class="input-wrapper">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" id="name" class="form-control" value="{{ $karyawan->name }}" name="name" placeholder="Nama Lengkap" autocomplete="off">
            </div>
        </div>

        {{-- Email --}}
        <div class="form-group boxed">
            <div class="input-wrapper">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" class="form-control" value="{{ $karyawan->email }}" name="email" placeholder="Alamat Email" autocomplete="off">
            </div>
        </div>

        {{-- Nomor HP --}}
        <div class="form-group boxed">
            <div class="input-wrapper">
                <label for="nohp" class="form-label">Nomor HP</label>
                <input type="text" id="nohp" class="form-control" value="{{ $karyawan->nohp }}" name="nohp" placeholder="Nomor HP" autocomplete="off">
            </div>
        </div>

        {{-- Alamat --}}
        <div class="form-group boxed">
            <div class="input-wrapper">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea id="alamat" class="form-control" name="alamat" rows="2" placeholder="Alamat">{{ $karyawan->alamat }}</textarea>
            </div>
        </div>

        {{-- PASSWORD DENGAN TOGGLE --}}
        <div class="mb-3 position-relative">
            <label class="form-label">Password</label>
            <div class="position-relative">
                <input 
                    type="password" 
                    name="password" 
                    class="form-control pe-5" 
                    placeholder="Kosongkan jika tidak diubah" 
                    id="passwordField" 
                    autocomplete="off"
                >
                <button 
                    type="button" 
                    class="btn btn-link position-absolute toggle-password text-secondary" 
                    style="
                        top: 50%; 
                        right: 0.75rem; 
                        transform: translateY(-50%);
                        padding: 0;
                        height: 100%;
                        display: flex; 
                        align-items: center;
                    ">
                    <ion-icon name="eye-outline" size="small"></ion-icon>
                </button>
            </div>
        </div>

        {{-- Upload Foto --}}
        <div class="form-group boxed">
            <label class="form-label">Foto Profil</label>
            <div class="custom-file-upload" id="fileUpload1">
                <input type="file" name="foto" id="fileuploadInput" accept=".png, .jpg, .jpeg">
                <label for="fileuploadInput">
                    <span>
                        <strong>
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <i>Tap untuk Upload Foto</i>
                        </strong>
                    </span>
                </label>
            </div>
            @if(!empty($karyawan->foto))
                <div class="mt-2 text-center">
                    <img src="{{ asset('storage/uploads/karyawan/'.$karyawan->foto) }}" alt="Foto Profil" class="img-fluid rounded" width="100">
                </div>
            @endif
        </div>

        {{-- Tombol Update --}}
        <div class="form-group boxed mt-3">
            <div class="input-wrapper">
                <button type="submit" class="btn btn-primary btn-block">
                    <ion-icon name="refresh-outline"></ion-icon>
                    Update
                </button>
            </div>
        </div>

    </div>
</form>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-password').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const icon = this.querySelector('ion-icon');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.setAttribute('name', 'eye-off-outline');
                } else {
                    input.type = 'password';
                    icon.setAttribute('name', 'eye-outline');
                }
            });
        });
    });
</script>
