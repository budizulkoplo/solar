<x-app-layout>
    <x-slot name="pagetitle">Transaksi Project OUT</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Transaksi Project Keluar (OUT)</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalOut">
                            <i class="bi bi-file-earmark-minus"></i> Tambah Transaksi OUT
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbProjectOut" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Rekening</th>
                                <th>Kode Transaksi</th>
                                <th>Nominal</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal OUT -->
    <div class="modal fade" id="modalOut" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmProjectOut">
                    @csrf
                    <input type="hidden" name="mode" value="out">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Transaksi Project OUT</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        @include('transaksi.project._form')
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        @include('transaksi.project._script_out')
    </x-slot>
</x-app-layout>
