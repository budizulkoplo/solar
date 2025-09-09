@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('mobile.home') }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Input Transaksi Armada</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:40px">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('mobile.transaksi_armada.store') }}" method="POST">
        @csrf

        {{-- Plat Nomor --}}
        <div class="mb-2 position-relative">
            <label>Plat Nomor</label>
            <input type="text" id="nopol" class="form-control" placeholder="Masukkan plat nomor" autocomplete="off" autofocus>
            <input type="hidden" name="armada_id" id="armada_id">
            <div id="nopol-list" class="list-group position-absolute w-100"></div>
        </div>

        {{-- Dimensi (cm) --}}
        <div class="mb-2">
            <label>Panjang (cm)</label>
            <input type="number" name="panjang" id="panjang" class="form-control" required readonly>
        </div>

        <div class="mb-2">
            <label>Lebar (cm)</label>
            <input type="number" name="lebar" id="lebar" class="form-control" required readonly>
        </div>

        <div class="mb-2">
            <label>Tinggi (cm)</label>
            <input type="number" name="tinggi" id="tinggi" class="form-control" required readonly>
        </div>

        <div class="mb-2">
            <label>Plus (cm)</label>
            <input type="number" name="plus" class="form-control">
        </div>

        {{-- Project --}}
        <div class="mb-2">
            <label>Project</label>
            <input type="text" class="form-control" value="{{ session('nama_project') ?? '-' }}" readonly>
            <input type="hidden" name="project_id" value="{{ session('project_id') ?? '' }}">
        </div>


        <button type="submit" class="btn btn-primary w-100 mt-3">Simpan Transaksi</button>
    </form>
</div>

{{-- Script langsung tanpa yield --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nopolInput = document.getElementById('nopol');
    const armadaIdInput = document.getElementById('armada_id');
    const nopolList = document.getElementById('nopol-list');
    const panjangInput = document.getElementById('panjang');
    const lebarInput = document.getElementById('lebar');
    const tinggiInput = document.getElementById('tinggi');

    nopolInput.addEventListener('input', function() {
        const query = this.value.trim();
        if(query.length < 2) {
            nopolList.innerHTML = '';
            return;
        }

        fetch("{{ route('mobile.transaksi_armada.search') }}?q=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                nopolList.innerHTML = '';
                if(data.length === 0) return;

                data.forEach(item => {
                    const div = document.createElement('div');
                    div.classList.add('list-group-item', 'list-group-item-action');
                    div.textContent = item.nopol;

                    // simpan data dimensi di dataset
                    div.dataset.id = item.id;
                    div.dataset.panjang = item.panjang;
                    div.dataset.lebar = item.lebar;
                    div.dataset.tinggi = item.tinggi;

                    div.addEventListener('click', function() {
                        nopolInput.value = this.textContent;
                        armadaIdInput.value = this.dataset.id;

                        // isi otomatis dimensi
                        panjangInput.value = this.dataset.panjang;
                        lebarInput.value = this.dataset.lebar;
                        tinggiInput.value = this.dataset.tinggi;

                        nopolList.innerHTML = '';
                    });

                    nopolList.appendChild(div);
                });
            })
            .catch(err => console.error('Fetch error:', err));
    });

    // tutup dropdown saat klik di luar
    document.addEventListener('click', function(e) {
        if(!nopolInput.contains(e.target) && !nopolList.contains(e.target)) {
            nopolList.innerHTML = '';
        }
    });
});
</script>

<style>
/* Style dropdown autocomplete */
#nopol-list {
    z-index: 9999;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-top: none;
    background-color: #fff;
}

#nopol-list .list-group-item {
    cursor: pointer;
}
</style>
@endsection
