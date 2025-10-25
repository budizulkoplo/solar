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
    @endphp

    <div class="pageTitle">Presensi {{ $setting->nama_perusahaan ?? 'Perusahaan' }}</div>
    <div class="right"></div>
</div>

<style>
    .webcam-capture, .webcam-capture video {
        display: inline-block;
        width: 100% !important;
        margin: auto;
        height: auto !important;
        border-radius: 15px;
    }

    #map { height: 220px; border-radius: 10px; }

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
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: space-around;
        padding: 10px 15px;
        background: #fff;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    .sticky-absen-btn button {
        flex: 1;
        margin: 0 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 16px;
        font-weight: 600;
        padding: 12px 0;
        border-radius: 10px;
    }

    .content-wrapper { padding-bottom: 80px; }
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
var map, userMarker, kantorMarker, radiusCircle;

// 🟢 Inisialisasi Kamera
function initializeWebcam() {
    stopCamera();
    const constraints = {
        video: { facingMode: isBackCamera ? 'environment' : 'user', width: { ideal: 640 }, height: { ideal: 480 } }
    };
    navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            currentStream = stream;
            videoElement.srcObject = stream;
            videoElement.play();
        })
        .catch(() => {
            Swal.fire('Kamera Error', 'Pastikan izin kamera diaktifkan.', 'error');
        });
}

function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
}

$("#switchCamera").click(() => { isBackCamera = !isBackCamera; initializeWebcam(); });

// 🟡 Lokasi & Peta
function initializeLocation() {
    if (!navigator.geolocation) {
        Swal.fire('Browser Tidak Support', 'Browser Anda tidak mendukung geolocation', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
}

function successCallback(position) {
    lokasiAktif = true;
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    $("#lokasi").val(lat + "," + lng);

    if (!map) {
        map = L.map('map').setView([lat, lng], 18);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    }

    if (userMarker) map.removeLayer(userMarker);
    userMarker = L.marker([lat, lng]).addTo(map).bindPopup("Lokasi Anda").openPopup();

    // 🔹 Ambil lokasi unit kerja dari backend
    fetch('/mobile/presensi/get-unitkerja-location')
        .then(res => res.json())
        .then(data => {
            if (data.status === "success" && data.lokasi) {
                const [latKantor, lngKantor] = data.lokasi.split(',').map(Number);

                if (kantorMarker) map.removeLayer(kantorMarker);
                if (radiusCircle) map.removeLayer(radiusCircle);

                kantorMarker = L.marker([latKantor, lngKantor]).addTo(map).bindPopup("Titik Unit Kerja");
                radiusCircle = L.circle([latKantor, lngKantor], {
                    color: 'blue',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.2,
                    radius: 100
                }).addTo(map);

                const group = L.featureGroup([userMarker, kantorMarker, radiusCircle]);
                map.fitBounds(group.getBounds());
            }
        });
}

function errorCallback() {
    Swal.fire('Lokasi Error', 'Tidak dapat mendeteksi lokasi. Aktifkan GPS Anda.', 'warning');
}

// 🟠 Kirim Presensi (dengan validasi radius)
function kirimPresensi(inoutmode) {
    if (!lokasiAktif) {
        Swal.fire('Lokasi Belum Aktif', 'Tunggu hingga lokasi terdeteksi.', 'info');
        initializeLocation();
        return;
    }

    const lokasi = $("#lokasi").val();

    // 🔹 Cek radius terlebih dahulu
    $.post('/mobile/presensi/cek-radius', {
        _token: "{{ csrf_token() }}",
        lokasi: lokasi
    }, function(res) {
        if (res.status === "error") {
            Swal.fire('Di Luar Area', res.message, 'warning');
            return;
        }

        // 🔸 Jika aman, lanjut ambil foto & kirim presensi
        prosesKirimPresensi(inoutmode);
    }).fail(() => {
        Swal.fire('Error', 'Gagal memeriksa radius lokasi.', 'error');
    });
}

function prosesKirimPresensi(inoutmode) {
    Swal.fire({
        title: 'Sedang Memproses',
        html: 'Mengambil foto & lokasi...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
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
        data: { _token: "{{ csrf_token() }}", image: imageData, lokasi, inoutmode },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.status === "success") {
                notifikasi_in.play().catch(()=>{});
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '/dashboard'; // ✅ arahkan balik ke dashboard
                });
            } else {
                Swal.fire('Gagal!', response.message, 'error');
            }
        },
    });
}

// 🎯 Event klik
$("#absenMasuk").click(() => kirimPresensi(1));
$("#absenPulang").click(() => kirimPresensi(2));

// Scroll button
$("#scrollDownBtn").click(() => {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
});

// 🧩 On Ready
$(document).ready(function() {
    initializeWebcam();
    initializeLocation();
});
</script>
@endpush
