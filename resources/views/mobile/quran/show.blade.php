@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Qur'an</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

@php
    $riwayatAyat = collect($riwayat ?? [])->keyBy('ayat');
@endphp

<style>
    .arab-text {
        font-family: 'Scheherazade New', serif;
        font-size: 1.6rem;
        line-height: 2.6rem;
        text-align: right;
        direction: rtl;
    }
    .ayat-card {
        border-radius: 12px;
        position: relative;
        transition: all 0.3s ease;
    }
    .ayat-card.marked-senin {
        border: 2px solid #fd7e14;
        box-shadow: 0 0 10px rgba(253, 126, 20, 0.4);
    }
    .ayat-card.marked-rutin {
        border: 2px solid #198754;
        box-shadow: 0 0 10px rgba(25, 135, 84, 0.4);
    }
    .last-read-label {
        position: absolute;
        font-size: 0.7rem;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 8px;
        white-space: nowrap;
        z-index: 10;
    }
    .last-read-label.senin {
        top: -10px;
        left: 10px;
        color: #fff;
        background: #fd7e14;
    }
    .last-read-label.rutin {
        top: -10px;
        right: 10px;
        color: #fff;
        background: #198754;
    }
    .choice-btn {
        padding: 12px;
        margin: 10px 0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .choice-btn.senin {
        background-color: rgba(253, 126, 20, 0.1);
        color: #fd7e14;
    }
    .choice-btn.rutin {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    .choice-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="p-3" style="margin-top: 40px">
    <div class="text-center mb-4">
        <h1 class="mb-0">{{ $surat['namaLatin'] }} <small class="text-muted">({{ $surat['arti'] }})</small></h1>
        <h2 class="mt-2" style="font-family: 'Scheherazade New', serif;">{{ $surat['nama'] }}</h2>
        <p class="mb-1"><strong>Jumlah Ayat:</strong> {{ $surat['jumlahAyat'] }}</p>
        <p><strong>Tempat Turun:</strong> {{ $surat['tempatTurun'] }}</p>
    </div>

    <div class="d-flex justify-content-center flex-wrap mb-4">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeFull" value="full" checked>
            <label class="form-check-label" for="modeFull">Lengkap</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeArab" value="arab">
            <label class="form-check-label" for="modeArab">Arab saja</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeArabTerjemah" value="arab_terjemah">
            <label class="form-check-label" for="modeArabTerjemah">Arab + Terjemahan</label>
        </div>
    </div>

    <div class="text-center mb-4">
        <button id="fontIncrease" class="btn btn-outline-primary btn-sm me-2">Perbesar+</button>
        <button id="fontDecrease" class="btn btn-outline-primary btn-sm">Perkecil-</button>
    </div>

    @foreach($surat['ayat'] as $a)
        @php
            $ayatRiwayat = $riwayatAyat->get($a['nomorAyat']);
            $isSenin = (bool) data_get($ayatRiwayat, 'senin', false);
            $isRutin = (bool) data_get($ayatRiwayat, 'rutin', false);
        @endphp
        <div class="card mb-3 shadow-sm ayat-card {{ $isSenin ? 'marked-senin' : '' }} {{ $isRutin ? 'marked-rutin' : '' }}"
             data-ayat="{{ $a['nomorAyat'] }}"
             onclick="handleAyatClick({{ $a['nomorAyat'] }})">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-primary px-3 py-2">Ayat {{ $a['nomorAyat'] }}</span>
                    <h3 class="arab-text flex-grow-1 ms-3">{{ $a['teksArab'] }}</h3>
                </div>

                <div class="latin mb-2">
                    <p><em>{{ $a['teksLatin'] }}</em></p>
                </div>
                <div class="terjemah">
                    <p>{{ $a['teksIndonesia'] }}</p>
                </div>

                <audio controls preload="none" class="ayat-audio w-100" data-ayat="{{ $a['nomorAyat'] }}">
                    <source src="{{ $a['audio']['05'] }}" type="audio/mpeg">
                    Browser anda tidak mendukung pemutar audio.
                </audio>

                <span class="last-read-label senin {{ $isSenin ? '' : 'd-none' }}">Senin Pagi</span>
                <span class="last-read-label rutin {{ $isRutin ? '' : 'd-none' }}">Ngaji Rutin</span>
            </div>
        </div>
    @endforeach

    <div class="d-flex justify-content-between mt-4 gap-2">
        @if($surat['suratSebelumnya'])
            <a href="{{ route('mobile.quran.show', $surat['suratSebelumnya']['nomor']) }}" class="btn btn-outline-secondary">
                ← {{ $surat['suratSebelumnya']['namaLatin'] }}
            </a>
        @else
            <div></div>
        @endif

        @if($surat['suratSelanjutnya'])
            <a href="{{ route('mobile.quran.show', $surat['suratSelanjutnya']['nomor']) }}" class="btn btn-outline-primary">
                {{ $surat['suratSelanjutnya']['namaLatin'] }} →
            </a>
        @endif
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('mobile.quran.index') }}" class="btn btn-dark">Kembali ke Daftar Surat</a>
    </div>
</div>

@include('mobile.quran.partials.doa-pagi')

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Jenis Aktivitas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>Pilih jenis aktivitas membaca:</p>
                <div class="choice-btn senin" data-type="senin">Senin Pagi</div>
                <div class="choice-btn rutin" data-type="rutin">Ngaji Rutin</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const radios = document.querySelectorAll('input[name="mode"]');
    const cards = document.querySelectorAll(".ayat-card");
    const arabTexts = document.querySelectorAll(".arab-text");
    const audios = document.querySelectorAll(".ayat-audio");
    const storageKey = "lastRead-{{ $surat['nomor'] }}";
    let currentAyat = null;

    window.handleAyatClick = function(no) {
        currentAyat = no;
        $("#activityModal").modal("show");
    };

    document.querySelectorAll('.choice-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            $("#activityModal").modal("hide");
            markAyat(currentAyat, this.dataset.type);
        });
    });

    function markAyat(no, activityType) {
        const card = document.querySelector(`.ayat-card[data-ayat='${no}']`);
        if (!card) {
            return;
        }

        const seninLabel = card.querySelector(".last-read-label.senin");
        const rutinLabel = card.querySelector(".last-read-label.rutin");

        if (activityType === "senin") {
            toggleMark(card, seninLabel, "marked-senin");
        } else if (activityType === "rutin") {
            const isMarked = card.classList.contains("marked-rutin");
            toggleMark(card, rutinLabel, "marked-rutin");
            if (!isMarked) {
                saveRutinToServer(no);
            }
        }

        saveToLocalStorage(no, activityType);
    }

    function toggleMark(card, label, className) {
        if (card.classList.contains(className)) {
            card.classList.remove(className);
            label.classList.add("d-none");
        } else {
            card.classList.add(className);
            label.classList.remove("d-none");
        }
    }

    function saveToLocalStorage(ayat, activityType) {
        let savedData = localStorage.getItem(storageKey);
        let data = savedData ? JSON.parse(savedData) : {};

        if (!data[ayat]) {
            data[ayat] = { senin: false, rutin: false };
        }

        data[ayat][activityType] = !data[ayat][activityType];

        if (!data[ayat].senin && !data[ayat].rutin) {
            delete data[ayat];
        }

        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    function loadFromLocalStorage() {
        const savedData = localStorage.getItem(storageKey);
        if (!savedData) {
            return;
        }

        try {
            const data = JSON.parse(savedData);
            for (const [ayat, activities] of Object.entries(data)) {
                const card = document.querySelector(`.ayat-card[data-ayat='${ayat}']`);
                if (!card) {
                    continue;
                }

                if (activities.senin && !card.classList.contains("marked-senin")) {
                    toggleMark(card, card.querySelector(".last-read-label.senin"), "marked-senin");
                }
                if (activities.rutin && !card.classList.contains("marked-rutin")) {
                    toggleMark(card, card.querySelector(".last-read-label.rutin"), "marked-rutin");
                }
            }
        } catch (error) {
            console.error("Error parsing last read data:", error);
        }
    }

    function saveRutinToServer(ayatNo) {
        fetch("{{ route('mobile.quran.markRutin') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                surat: {!! json_encode($surat['namaLatin'], JSON_UNESCAPED_UNICODE) !!},
                ayat: ayatNo
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.warn("Gagal simpan penanda ayat.");
            }
        })
        .catch(err => console.error("Error saving:", err));
    }

    loadFromLocalStorage();

    radios.forEach(radio => {
        radio.addEventListener("change", function () {
            const mode = this.value;
            cards.forEach(card => {
                const latin = card.querySelector(".latin");
                const terjemah = card.querySelector(".terjemah");
                const audio = card.querySelector(".ayat-audio");

                if (mode === "full") {
                    latin.style.display = "block";
                    terjemah.style.display = "block";
                    audio.style.display = "block";
                } else if (mode === "arab") {
                    latin.style.display = "none";
                    terjemah.style.display = "none";
                    audio.style.display = "none";
                } else if (mode === "arab_terjemah") {
                    latin.style.display = "none";
                    terjemah.style.display = "block";
                    audio.style.display = "block";
                }
            });
        });
    });

    audios.forEach((audio, index) => {
        audio.addEventListener("ended", () => {
            const next = audios[index + 1];
            if (next) {
                next.play();
                next.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    });

    let fontSize = 1.6;
    document.getElementById("fontIncrease")?.addEventListener("click", () => {
        fontSize += 0.2;
        arabTexts.forEach(t => t.style.fontSize = fontSize + "rem");
    });
    document.getElementById("fontDecrease")?.addEventListener("click", () => {
        if (fontSize > 1.2) {
            fontSize -= 0.2;
            arabTexts.forEach(t => t.style.fontSize = fontSize + "rem");
        }
    });
});
</script>
@endsection
