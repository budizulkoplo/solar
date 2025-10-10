@extends('layouts.mobile')

@section('header')
    <div class="appHeader bg-primary text-light">
        <div class="left">
            <a href="javascript:;" class="headerButton goBack">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </a>
        </div>

        @php
            use App\Models\Setting;
            $setting = Setting::first();

            function singkatPerusahaan($nama) {
                $parts = explode(' ', trim($nama));
                $result = '';
                foreach ($parts as $p) {
                    if (strlen($p) > 2) {
                        $result .= strtoupper(substr($p, 0, 1));
                    }
                }
                return $result;
            }

            $namaPendek = singkatPerusahaan($setting->nama_perusahaan ?? 'Perusahaan');
        @endphp

        <div class="pageTitle">Presensi {{ $setting->nama_perusahaan ?? 'Perusahaan' }}</div>
        <div class="right"></div>
    </div>

    <style>
        .webcam-capture,
        .webcam-capture video {
            display: inline-block;
            width: 100% !important;
            margin: auto;
            height: auto !important;
            border-radius: 15px;
        }

        #map { height: 200px; }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .section-title {
            font-weight: bold;
            margin: 15px 0 10px 0;
            font-size: 16px;
            color: #333;
        }

        .scroll-down-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1000;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3880ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .sticky-absen-btn {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 0 15px;
        }

        .sticky-absen-btn button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
            gap: 6px;
            padding: 12px 0;
            border: none;
            border-radius: 10px;
            color: #fff;
        }

        .content-wrapper {
            padding-bottom: 80px;
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<div class="content-wrapper">

    <div class="row">
        <div class="col">
            <div class="section-title">Ambil Foto Presensi</div>
            <input type="hidden" id="lokasi">
            <div class="webcam-capture">
                <video autoplay playsinline></video>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="button-container">
                <button id="switchCamera" class="btn btn-secondary btn-block">
                    <ion-icon name="camera-reverse-outline"></ion-icon> Switch Camera
                </button>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col">
            <div class="section-title">Lokasi Anda</div>
            <div id="map"></div>
        </div>
    </div>

    <button id="scrollDownBtn" class="scroll-down-btn">
        <ion-icon name="chevron-down-outline"></ion-icon>
    </button>
</div>

<!-- Tombol Absen -->
<div class="sticky-absen-btn">
    <button id="absenMasuk" class="btn btn-primary">
        <ion-icon name="camera-outline"></ion-icon> Absen Masuk
    </button>
    <button id="absenPulang" class="btn btn-danger">
        <ion-icon name="exit-outline"></ion-icon> Absen Pulang
    </button>
</div>

<audio id="notifikasi_in">
    <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
</audio>
@endsection

@push('myscript')
<script>
    var notifikasi_in = document.getElementById('notifikasi_in');
    var lokasiAktif = false;
    var currentStream = null;
    var isBackCamera = false;
    var videoElement = document.querySelector('.webcam-capture video');

    // Inisialisasi Kamera
    function initializeWebcam() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            stopCamera();

            const constraints = {
                video: {
                    facingMode: isBackCamera ? 'environment' : 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(stream) {
                    currentStream = stream;
                    videoElement.srcObject = stream;
                    videoElement.play();
                })
                .catch(function(error) {
                    Swal.fire({
                        title: 'Kamera Error',
                        text: 'Gagal mengakses kamera. Pastikan izin kamera diaktifkan.',
                        icon: 'error'
                    });
                });
        }
    }

    function stopCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
    }

    document.getElementById('switchCamera').addEventListener('click', function() {
        isBackCamera = !isBackCamera;
        initializeWebcam();
    });

    // Lokasi
    function initializeLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        } else {
            Swal.fire({
                title: 'Browser Tidak Support',
                text: 'Browser Anda tidak mendukung geolocation',
                icon: 'error'
            });
        }
    }

    function successCallback(position) {
        lokasiAktif = true;
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        $("#lokasi").val(lat + "," + lng);

        var map = L.map('map').setView([lat, lng], 18);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        L.marker([lat, lng]).addTo(map).bindPopup("Lokasi Anda").openPopup();
    }

    function errorCallback(error) {
        Swal.fire({
            title: 'Lokasi Error',
            text: 'Tidak dapat mendeteksi lokasi, silakan aktifkan GPS.',
            icon: 'warning'
        });
    }

    // Fungsi umum absen
    function kirimPresensi(inoutmode) {
        if (!lokasiAktif) {
            Swal.fire({
                title: 'Lokasi Belum Aktif',
                text: 'Tunggu hingga lokasi terdeteksi.',
                icon: 'info'
            });
            initializeLocation();
            return;
        }

        Swal.fire({
            title: 'Sedang Memproses',
            html: 'Mengambil foto & lokasi...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        canvas.getContext('2d').drawImage(videoElement, 0, 0, canvas.width, canvas.height);

        const imageData = canvas.toDataURL('image/jpeg');
        const lokasi = $("#lokasi").val();

        $.ajax({
            type: 'POST',
            url: '/mobile/presensi/store',
            data: {
                _token: "{{ csrf_token() }}",
                image: imageData,
                lokasi: lokasi,
                inoutmode: inoutmode
            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === "success") {
                    notifikasi_in.play().catch(()=>{});
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message,
                        icon: 'success',
                        timer: 4000,
                        showConfirmButton: false
                    }).then(() => window.location.href = '/dashboard');
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan pada server.',
                    icon: 'error'
                });
            }
        });
    }

    // Event klik
    $("#absenMasuk").click(function() { kirimPresensi(1); });
    $("#absenPulang").click(function() { kirimPresensi(2); });

    // Scroll button
    document.getElementById('scrollDownBtn').addEventListener('click', function() {
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    });

    $(document).ready(function() {
        initializeWebcam();
        initializeLocation();
    });
</script>
@endpush
