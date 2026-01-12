@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Presensi Visit</div>
    <div class="right">
        <a href="{{ route('mobile.presensi.historiVisit') }}" class="headerButton">
            <ion-icon name="time-outline"></ion-icon>
        </a>
    </div>
</div>

<style>
    .webcam-capture, .webcam-capture video {
        display: inline-block;
        width: 100% !important;
        margin: auto;
        height: auto !important;
        
    }

    #map { 
        height: 200px; 
        border-radius: 10px; 
        margin-bottom: 15px;
        border: 2px solid #dee2e6;
    }

    .status-presensi {
        display: flex;
        justify-content: space-around;
        margin: 15px 0;
        gap: 10px;
    }
    
    .status-item {
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        background-color: #f8f9fa;
        flex: 1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .status-item.active {
        background-color: #198754;
        color: white;
        box-shadow: 0 3px 6px rgba(25, 135, 84, 0.3);
    }
    
    .status-item.pending {
        background-color: #ffc107;
        color: #000;
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
        box-shadow: 0 -2px 10px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    .sticky-absen-btn button {
        flex: 1;
        margin: 0 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 600;
        padding: 14px 0;
        border-radius: 12px;
        transition: all 0.3s;
    }
    
    .sticky-absen-btn button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .content-wrapper { padding-bottom: 100px; }
    
    .section-title {
        font-weight: bold;
        margin: 15px 0 8px 0;
        font-size: 16px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .section-title ion-icon {
        font-size: 18px;
    }
    
    .location-info {
        background: #e7f8ff;
        border-radius: 8px;
        padding: 10px;
        margin: 10px 0;
        border-left: 4px solid #0dcaf0;
    }
    
    .keterangan-box {
        margin: 15px 0;
    }
    
    .btn-switch-camera {
        width: 100%;
        margin: 10px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">

    <!-- Status Presensi -->
    <div class="status-presensi">
        <div class="status-item {{ $cekMasuk > 0 ? 'active' : 'pending' }}">
            <div><strong>VISIT MASUK</strong></div>
            <small>{{ $cekMasuk > 0 ? '✓ SUDAH ABSEN' : 'BELUM ABSEN' }}</small>
        </div>
        <div class="status-item {{ $cekPulang > 0 ? 'active' : 'pending' }}">
            <div><strong>VISIT PULANG</strong></div>
            <small>{{ $cekPulang > 0 ? '✓ SUDAH ABSEN' : 'BELUM ABSEN' }}</small>
        </div>
    </div>

    <!-- Keterangan Tugas Luar (WAJIB) -->
    <div class="section-title">
        <ion-icon name="document-text-outline"></ion-icon>
        Keterangan Visit
    </div>
    <div class="keterangan-box">
        <textarea id="keterangan" class="form-control" rows="3" 
                  placeholder="📝 WAJIB diisi: Tujuan kunjungan/tugas luar...
Contoh: 
• Meeting dengan PT. ABC 
• Kunjungan ke klien 
• Survey lokasi proyek 
• Installasi di site 
• Presentasi produk 
• Dinas luar lainnya" 
                  required></textarea>
        <small class="text-muted">Isi dengan jelas tujuan visit/tugas luar Anda</small>
    </div>

    <!-- Kamera -->
    <div class="section-title">
        <ion-icon name="camera-outline"></ion-icon>
        Foto Presensi
    </div>
    <input type="hidden" id="lokasi">
    <div class="webcam-capture">
        <video autoplay playsinline></video>
    </div>
    
    <!-- Tombol Switch Kamera -->
    <button id="switchCamera" class="btn btn-outline-secondary btn-switch-camera">
        <ion-icon name="camera-reverse-outline"></ion-icon> Ganti Kamera
    </button>

    <!-- Peta Lokasi -->
    <div class="section-title">
        <ion-icon name="location-outline"></ion-icon>
        Lokasi Saat Ini
    </div>
    <div id="map"></div>
    
    <div class="location-info">
        <div class="d-flex align-items-center">
            <ion-icon name="information-circle-outline" class="me-2"></ion-icon>
            <div>
                <small><strong>📍 Presensi Visit</strong> - Untuk tugas luar/kunjungan</small><br>
                <small class="text-muted">Lokasi akan otomatis terekam saat absen</small>
            </div>
        </div>
    </div>
    
    <div class="alert alert-light mt-3">
        <div class="d-flex">
            <ion-icon name="time-outline" class="me-2"></ion-icon>
            <div>
                <small><strong>Hari/Tanggal:</strong> {{ date('l, d F Y') }}</small><br>
                <small><strong>Jam:</strong> <span id="currentTime">{{ date('H:i:s') }}</span></small>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Absen Visit -->
<div class="sticky-absen-btn">
    <button id="absenMasukVisit" class="btn btn-primary" {{ $cekMasuk > 0 ? 'disabled' : '' }}>
        <ion-icon name="log-in-outline"></ion-icon> Visit Masuk
    </button>
    <button id="absenPulangVisit" class="btn btn-danger" {{ $cekPulang > 0 ? 'disabled' : '' }}>
        <ion-icon name="log-out-outline"></ion-icon> Visit Pulang
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
var isBackCamera = true; // Default kamera belakang untuk visit
var videoElement = document.querySelector('.webcam-capture video');
var map, userMarker;
var lokasiCoords = null;

// 🟢 Inisialisasi Kamera
function initializeWebcam() {
    stopCamera();
    const constraints = {
        video: { 
            facingMode: isBackCamera ? 'environment' : 'user', 
            width: { ideal: 640 }, 
            height: { ideal: 480 },
            aspectRatio: { ideal: 4/3 }
        }
    };
    
    navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            currentStream = stream;
            videoElement.srcObject = stream;
            videoElement.play();
        })
        .catch((error) => {
            console.error('Camera error:', error);
            // Fallback ke kamera depan jika belakang tidak tersedia
            isBackCamera = false;
            const fallbackConstraints = {
                video: { 
                    facingMode: 'user',
                    width: { ideal: 640 }, 
                    height: { ideal: 480 }
                }
            };
            
            navigator.mediaDevices.getUserMedia(fallbackConstraints)
                .then(stream => {
                    currentStream = stream;
                    videoElement.srcObject = stream;
                    videoElement.play();
                })
                .catch(() => {
                    Swal.fire('Kamera Tidak Tersedia', 'Aktifkan izin kamera di pengaturan browser Anda.', 'error');
                });
        });
}

function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
}

$("#switchCamera").click(() => { 
    isBackCamera = !isBackCamera; 
    initializeWebcam(); 
});

// 🟡 Lokasi & Peta (SIMPLE - HANYA TAMPILKAN LOKASI SAAT INI)
function initializeLocation() {
    if (!navigator.geolocation) {
        showLocationError('Browser tidak mendukung geolocation');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        successCallback, 
        errorCallback, 
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function successCallback(position) {
    lokasiAktif = true;
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    lokasiCoords = { lat, lng };
    
    $("#lokasi").val(lat + "," + lng);

    // Inisialisasi peta jika belum ada
    if (!map) {
        map = L.map('map').setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);
    }

    // Hapus marker lama jika ada
    if (userMarker) {
        map.removeLayer(userMarker);
    }

    // Tambahkan marker lokasi saat ini
    userMarker = L.marker([lat, lng]).addTo(map)
        .bindPopup(`
            <div style="text-align: center;">
                <strong>📍 Lokasi Anda</strong><br>
                ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>
                <small>${new Date().toLocaleTimeString('id-ID')}</small>
            </div>
        `)
        .openPopup();
}

function errorCallback(error) {
    console.error('Geolocation error:', error);
    
    let errorMessage = 'Tidak dapat mendeteksi lokasi';
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMessage = 'Izin lokasi ditolak. Aktifkan GPS/Lokasi di pengaturan.';
            break;
        case error.POSITION_UNAVAILABLE:
            errorMessage = 'Informasi lokasi tidak tersedia.';
            break;
        case error.TIMEOUT:
            errorMessage = 'Waktu tunggu lokasi habis.';
            break;
    }
    
    showLocationError(errorMessage);
}

function showLocationError(message) {
    // Tampilkan pesan error di map container
    if (map) {
        map.remove();
    }
    
    const mapContainer = document.getElementById('map');
    mapContainer.innerHTML = `
        <div style="height: 100%; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center;">
            <div>
                <ion-icon name="location-off-outline" style="font-size: 48px; color: #dc3545;"></ion-icon>
                <p class="mt-2">${message}</p>
                <button onclick="initializeLocation()" class="btn btn-sm btn-outline-primary mt-2">
                    <ion-icon name="refresh-outline"></ion-icon> Coba Lagi
                </button>
            </div>
        </div>
    `;
}

// 🔴 Kirim Presensi Visit (TANPA VALIDASI RADIUS)
function kirimPresensiVisit(inoutmode) {
    // Validasi keterangan wajib
    const keterangan = $("#keterangan").val().trim();
    if (!keterangan) {
        Swal.fire({
            title: 'Keterangan Wajib',
            text: 'Harap isi keterangan tugas luar/kunjungan terlebih dahulu.',
            icon: 'warning',
            confirmButtonText: 'OK',
            focusConfirm: false
        }).then(() => {
            $("#keterangan").focus();
        });
        return;
    }

    // Validasi lokasi
    if (!lokasiAktif || !lokasiCoords) {
        Swal.fire({
            title: 'Lokasi Belum Siap',
            text: 'Lokasi belum terdeteksi. Pastikan GPS aktif.',
            icon: 'info',
            confirmButtonText: 'OK'
        });
        initializeLocation();
        return;
    }

    // Konfirmasi sebelum absen
    const modeText = inoutmode == 1 ? 'VISIT MASUK' : 'VISIT PULANG';
    Swal.fire({
        title: `Absen ${modeText}?`,
        html: `
            <div style="text-align: left;">
                <p><strong>Konfirmasi Presensi Visit:</strong></p>
                <p>📍 <strong>Lokasi:</strong> ${lokasiCoords.lat.toFixed(6)}, ${lokasiCoords.lng.toFixed(6)}</p>
                <p>📝 <strong>Keterangan:</strong> ${keterangan.substring(0, 50)}${keterangan.length > 50 ? '...' : ''}</p>
                <p>🕒 <strong>Waktu:</strong> ${new Date().toLocaleTimeString('id-ID')}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `<ion-icon name="checkmark-circle-outline"></ion-icon> Ya, Absen ${modeText}`,
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            prosesKirimPresensiVisit(inoutmode, keterangan);
        }
    });
}

function prosesKirimPresensiVisit(inoutmode, keterangan) {
    // Tampilkan loading
    Swal.fire({
        title: 'Memproses...',
        html: `
            <div style="text-align: center;">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p>Mengambil foto & mengirim data...</p>
                <small class="text-muted">Pastikan wajah terlihat jelas di kamera</small>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            // Ambil foto dari kamera
            setTimeout(() => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    const context = canvas.getContext('2d');
                    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    const lokasi = $("#lokasi").val();

                    // Kirim data ke server
                    $.ajax({
                        type: 'POST',
                        url: '/mobile/presensi/store-visit',
                        data: { 
                            _token: "{{ csrf_token() }}", 
                            image: imageData, 
                            lokasi: lokasi, 
                            keterangan: keterangan,
                            inoutmode: inoutmode 
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.status === "success") {
                                // Play sound notification
                                notifikasi_in.play().catch(() => {
                                    // Jika audio gagal, tetap lanjut
                                });
                                
                                // Tampilkan sukses
                                Swal.fire({
                                    title: '✅ Berhasil!',
                                    html: `
                                        <div style="text-align: center;">
                                            <p style="font-size: 18px; color: #198754;">${response.message}</p>
                                            <p class="text-muted">Presensi visit telah tersimpan</p>
                                            <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-top: 10px;">
                                                <small>📍 Lokasi: ${lokasiCoords.lat.toFixed(6)}, ${lokasiCoords.lng.toFixed(6)}</small><br>
                                                <small>📝 Keterangan: ${keterangan.substring(0, 40)}${keterangan.length > 40 ? '...' : ''}</small>
                                            </div>
                                        </div>
                                    `,
                                    icon: 'success',
                                    confirmButtonColor: '#198754',
                                    confirmButtonText: 'OK',
                                    willClose: () => {
                                        // Redirect atau reload halaman
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: '❌ Gagal',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            console.error('Ajax error:', error);
                            Swal.fire({
                                title: '⚠️ Error',
                                text: 'Terjadi kesalahan saat mengirim data. Coba lagi.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                } catch (error) {
                    Swal.close();
                    console.error('Canvas error:', error);
                    Swal.fire({
                        title: '⚠️ Error Kamera',
                        text: 'Gagal mengambil foto. Pastikan kamera berfungsi.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }, 1000); // Delay 1 detik untuk memastikan kamera siap
        }
    });
}

// 🎯 Event klik untuk tombol visit
$("#absenMasukVisit").click(() => kirimPresensiVisit(1));
$("#absenPulangVisit").click(() => kirimPresensiVisit(2));

// 🧩 On Ready
$(document).ready(function() {
    // Update waktu real-time
    function updateTime() {
        $('#currentTime').text(new Date().toLocaleTimeString('id-ID'));
    }
    setInterval(updateTime, 1000);
    
    // Inisialisasi kamera
    initializeWebcam();
    
    // Inisialisasi lokasi dengan delay
    setTimeout(initializeLocation, 500);
    
    // Refresh lokasi setiap 1 menit
    setInterval(initializeLocation, 60000);
    
    // Fokus ke textarea keterangan
    setTimeout(() => {
        $("#keterangan").focus();
    }, 1000);
    
    // Handle enter di textarea (tidak submit, hanya new line)
    $("#keterangan").keydown(function(e) {
        if (e.keyCode === 13 && !e.shiftKey) {
            e.preventDefault();
            // Bisa tambahkan fungsi lain jika perlu
        }
    });
});
</script>
@endpush