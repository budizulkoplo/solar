<x-app-layout>
    <x-slot name="pagetitle">Unit</x-slot>
    <div class="app-content-header"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Master Data Unit</h3>
                </div>
            </div> <!--end::Row-->
        </div> <!--end::Container-->
    </div>
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="app-content"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info card-outline mb-4"> <!--begin::Header-->
                        <div class="card-header pt-1 pb-1">
                            <div class="card-title">
                            </div>
                            <div class="card-tools"> 
                                <a href="{{ route('unit.add') }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-plus"></i></a>
                            </div>
                        </div> <!--end::Header--> <!--begin::Body-->
                        <div class="card-body">
                            <table id="example" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                                <thead>
                                    <tr>
                                        <th>Nama.</th>
                                        <th>Jenis</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($units as $item)
                                        <tr>
                                            <td>{{ $item->nama_unit }}</td>
                                            <td>{{ $item->jenis }}</td>
                                            <td>
                                                <a href="{{ route('unit.edit',['id' => Crypt::encryptString($item->id)]) }}"><span class="badge bg-primary"><i class="bi bi-pencil-square"></span></i></a>
                                                <a href="{{ route('unit.Hapus',['id' => Crypt::encryptString($item->id)]) }}" class="confirm-link"><span class="badge bg-danger"><i class="bi bi-trash3-fill"></i></span></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('users.updatepassword') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="userid" id="tuserid" required>
                    <div class="row">
                        <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1">New Password</span>
                        <input type="password" name="new_password" id="tpassword" class="form-control" placeholder="" aria-label="Password" aria-describedby="basic-addon1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saverole">Save changes</button>
                </div>
            </form>
        </div>
        </div>
    </div>
    <x-slot name="csscustom">
    </x-slot>
    <x-slot name="jscustom">
        <script>
            var table = $('#example').DataTable({
                ordering: false,"responsive": true
            });
            $( document ).ready(function() {
                $('.confirm-link').on('click', function(e) {
                    e.preventDefault(); // Cegah link langsung dijalankan
                    const url = $(this).attr('href');

                    if (confirm('Apakah Anda yakin ingin melanjutkan?')) {
                        window.location.href = url; // Lanjutkan ke link jika dikonfirmasi
                    }
                });

            });
        </script>
    </x-slot>
</x-app-layout>