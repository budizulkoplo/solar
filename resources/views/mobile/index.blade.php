@extends('layouts.mobile')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="avatar">
            @if($user->foto)
                <img src="{{ asset('storage/foto/' . $user->foto) }}" alt="avatar" loading="lazy">
            @else
                <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
            @endif
        </div>
        
        <div id="user-info">
             @php
                use App\Models\Setting;
                $setting = Setting::first();

                function singkatPerusahaan($nama) {
                    $parts = explode(' ', trim($nama));
                    $result = '';
                    foreach ($parts as $p) {
                        if (strlen($p) > 2) { // ambil huruf depan kecuali "PT."
                            $result .= strtoupper(substr($p, 0, 1));
                        }
                    }
                    return $result;
                }

                $namaPendek = singkatPerusahaan($setting->nama_perusahaan ?? 'Perusahaan');
            @endphp
            <div id="user-role">{{ $setting->nama_perusahaan }}</div>
            <h3>{{ $user->name }}</h3>
            <div id="user-role">Project: <strong>{{ $namaProject ?? '-' }}</strong></div>

        </div>
    </div>
</div>

<div class="performance-card">
    <div class="title">Data Pengguna</div>

    <div class="performance-grid">
        <div class="perf-item perf-saldo text-decoration-none">
            <ion-icon name="id-card-outline"></ion-icon>
            <div class="perf-text">
                <div class="label">Nomor Anggota</div>
                <div class="value">{{ $user->nomor_anggota }}</div>
            </div>
        </div>

        <div class="perf-item perf-saldo text-decoration-none">
            <ion-icon name="calendar-outline"></ion-icon>
            <div class="perf-text">
                <div class="label">Tanggal Registrasi</div>
                <div class="value">{{ \Carbon\Carbon::parse($user->tanggal_masuk)->format('d-m-Y') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="scrollable-content">
    <div class="rekappresensi">
        <h4 class="text-center">Kontak & Informasi Lain</h4>
        <ul class="list-group mb-3">
            <li class="list-group-item"><strong>Email:</strong> {{ $user->email }}</li>
            <li class="list-group-item"><strong>No HP:</strong> {{ $user->nohp }}</li>
            <li class="list-group-item"><strong>Alamat:</strong> {{ $user->alamat }}</li>
        </ul>
    </div>
</div>

@endsection
