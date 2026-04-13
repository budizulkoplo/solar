@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('mobile.home') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Qur'an</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

<style>
    .arab-title {
        font-family: 'Scheherazade New', serif;
        font-size: 1.6rem;
        text-align: right;
        direction: rtl;
    }
</style>

<div class="p-3" style="margin-top: 40px">
    <h2 class="mb-3">Daftar Surat</h2>

    <div class="mb-3">
        <input type="text" id="searchSurat" class="form-control" placeholder="Cari surat...">
    </div>

    <div class="list-group" id="suratList">
        @forelse($surat as $s)
            <a href="{{ route('mobile.quran.show', $s['nomor']) }}" class="list-group-item list-group-item-action surat-item">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <strong>{{ $s['namaLatin'] }}</strong> ({{ $s['jumlahAyat'] }} ayat)<br>
                        <small>{{ $s['arti'] }}</small>
                    </div>
                    <div class="arab-title">
                        {{ $s['nama'] }}
                    </div>
                </div>
            </a>
        @empty
            <div class="alert alert-warning mb-0">
                Data surat belum tersedia.
            </div>
        @endforelse
    </div>
</div>

@include('mobile.quran.partials.doa-pagi')

<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchSurat");
    const items = document.querySelectorAll(".surat-item");

    input?.addEventListener("keyup", function () {
        const filter = input.value.toLowerCase();
        items.forEach(item => {
            item.style.display = item.innerText.toLowerCase().includes(filter) ? "block" : "none";
        });
    });
});
</script>
@endsection
