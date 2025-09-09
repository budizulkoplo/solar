<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-6">
                    <!-- Profile Image -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                @if (isset(Auth::user()->foto) && Storage::disk('private')->exists("img/foto/".Auth::user()->foto))
                                    <img class="rounded mx-auto d-block" data-bs-toggle="modal" data-bs-target="#exampleModal" style="cursor: pointer;max-height: 150px;" src="{{ url('/doc/file/foto/'.Auth::user()->foto.'?t='. time()) }}" alt="User profile picture">
                                @else
                                    <img class="rounded-circle mx-auto d-block" data-bs-toggle="modal" data-bs-target="#exampleModal" style="width: 120px;cursor: pointer;" src="{{ url('/doc/file/foto/default.png') }}" alt="User profile picture">
                                @endif
                            </div>
                            <h3 class="profile-username text-center fs-4">{{ Auth::user()->name }}</h3>
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item"><b>No.Anggota</b> <span class="float-end">{{ Auth::user()->nomor_anggota }}</span></li>
                                <li class="list-group-item"><b>UserName</b> <span class="float-end">{{ Auth::user()->username }}</span></li>
                                <li class="list-group-item"><b>Jabatan</b> <span class="float-end">{{ Auth::user()->jabatan }}</span></li>
                                <li class="list-group-item"><b>Telp/HP</b> <span class="float-end">{{ Auth::user()->nohp }}</span></li>
                                <li class="list-group-item"><b>Limit PPOB</b> <span class="float-end">{{ format_rupiah(Auth::user()->limit_ppob) }}</span></li>
                                <li class="list-group-item"><b>Limit Hutang</b> <span class="float-end">{{ format_rupiah(Auth::user()->limit_hutang) }}</span></li>
                            </ul>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                <!-- /.card -->
                </div>
                <!-- Update Profile Information Form -->
                <div class="col-12 col-md-8 col-lg-6 mx-auto">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                        @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>
    
                <!-- Update Password Form -->
                <div class="col-12 col-md-8 col-lg-6 mx-auto">
                    <div class="p-4 bg-white shadow rounded">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
    
                <!-- Delete User Form -->
                <div class="col-12 col-md-8 col-lg-6 mx-auto">
                    <div class="p-4 bg-white shadow rounded">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Picture Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="myFormUpload" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-1">
                        <div class="text-center">
                            @if (isset(Auth::user()->foto) && Storage::disk('private')->exists("img/foto/".Auth::user()->foto))
                                <img src="{{ url('/doc/file/foto/'.Auth::user()->foto.'?t='. time()) }}" id="previewImage" class="img-fluid rounded" alt="...">
                            @else
                                <img src="{{ url('/doc/file/foto/default.png') }}" id="previewImage" class="rounded" alt="...">
                            @endif
                        </div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md mb-3">
                            <input type="file" class="form-control form-control-sm" id="inputGroupFile02" name="docfile" accept="image/jpg, image/jpeg">
                            <div id="emailHelp" class="form-text">
                                <ul style="list-style-type:circle">
                                    <li>format file *.JPG</li>
                                </ul>
                            </div>
                            <input type="file" id="fototmp" name="fototmp" accept="image/png, image/jpeg" style="display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" id="btnupload" class="btn btn-primary" style="display: none">Save changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    {{-- <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div> --}}
    <x-slot name="csscustom">
    </x-slot>
    <x-slot name="jscustom">
        <script>
            $('#inputGroupFile02').on('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.src = e.target.result;

                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // Maksimal resolusi
                        const maxWidth = 2500;
                        const maxHeight = 2500;
                        let width = img.width;
                        let height = img.height;

                        // Hitung skala
                        if (width > maxWidth || height > maxHeight) {
                            const scale = Math.min(maxWidth / width, maxHeight / height);
                            width = width * scale;
                            height = height * scale;
                        }

                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        // Paksa jadi JPEG untuk hasil kompres maksimal
                        canvas.toBlob(function (blob) {
                            const compressedFile = new File(
                                [blob],
                                `compressed-${Date.now()}.jpg`,
                                { type: 'image/jpeg' }
                            );

                            // Update file input dengan file hasil kompres
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(compressedFile);
                            document.getElementById('inputGroupFile02').files = dataTransfer.files;
                            document.getElementById('fototmp').files = dataTransfer.files;

                            // Preview hasil
                            const imagePreview = document.getElementById('previewImage');
                            imagePreview.src = URL.createObjectURL(compressedFile);

                            // Tampilkan tombol upload
                            document.getElementById('btnupload').style.display = 'inline-block';

                            // Debug log
                            console.log('Ukuran file asli:', (file.size / 1024).toFixed(2), 'KB');
                            console.log('Ukuran file hasil kompres:', (blob.size / 1024).toFixed(2), 'KB');
                            console.log('Resolusi asli:', img.width, 'x', img.height);
                            console.log('Resolusi baru:', width, 'x', height);
                        }, 'image/jpeg', 0.7); // kualitas kompres 70%
                    };
                };

                reader.readAsDataURL(file);
            });
            $('#myFormUpload').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('id', {{ Auth::user()->id }});
                formData.append('path', 'foto');
                Swal.fire({
                    title: 'Mohon tunggu...',
                    html: 'Sedang memproses data',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading(); // menampilkan spinner
                    }
                });
                $.ajax({
                    url: '{{ url('profile') }}', // Ganti dengan URL endpoint upload kamu
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    //beforeSend: function(xhr) {loader(true);},
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Upload berhasil'
                        });
                        //console.log('Upload berhasil:', response);
                        $('#fotoModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan: ' + xhr.responseText
                        });
                        //console.error('Upload gagal:', xhr.responseText);
                        $('#exampleModal').modal('hide');
                        //loader(false);
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>
