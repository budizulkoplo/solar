<x-app-layout>
    <x-slot name="pagetitle">Unit.::Form::.</x-slot>
    <div class="app-content-header"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $isEdit?'Edit':'Tambah' }} Unit</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('unit.list') }}">Daftar Unit</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Form Unit</li>
                    </ol>
                </div>
            </div> <!--end::Row-->
        </div> <!--end::Container-->
    </div>
    <div class="app-content"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row justify-content-md-center">
                <div class="col col-lg-6">
                    <div class="card card-info card-outline mb-4"> <!--begin::Header-->
                        <div class="card-body">
                            {{-- <div id='e1'></div> --}}
                            @if(!empty(session('success')) || !empty(session('error')))
                            <div class="alert alert-{{ !empty(session('success'))? 'info':'warning' }} alert-dismissible fade show" role="alert">
                                {{ !empty(session('success'))? session('success'):session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>
                            @endif
                            <form method="POST" autocomplete="off" class="needs-validation"  novalidate action="{{ $isEdit? route('unit.StorePut',['id' => Crypt::encryptString($unit->id)]) : route('unit.StorePost') }}">
                                @csrf
                                @if ($isEdit)
                                    @method('PUT')
                                @endif
                            <div class="row mb-3">
                                <div class="col-md mb-3"> 
                                    <label for="nama_unit" class="form-label">Nama</label> 
                                    <input type="text" class="form-control form-control-sm" name="nama_unit" required value="{{ $isEdit?$unit->nama_unit:'' }}">
                                </div>
                                <div class="col-md-auto">
                                    <label for="jenis" class="form-label">Jenis</label> 
                                    <select class="form-select form-select-sm" data-placeholder="Choose one thing" name="jenis" required>
                                        <option value="toko" {{ $isEdit && 'toko' == $unit->jenis?'selected':'' }}>Toko</option>
                                        <option value="bengkel" {{ $isEdit && 'bengkel' == $unit->bengkel?'selected':'' }}>Bengkel</option>
                                        <option value="gudang" {{ $isEdit && 'gudang' == $unit->gudang?'selected':'' }}>Gudang</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" id="btnsubmit">{{ $isEdit?'Edit':'Simpan' }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-slot name="csscustom">
    </x-slot>
    <x-slot name="jscustom">
    </x-slot>
</x-app-layout>