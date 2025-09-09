<x-app-layout>
    <x-slot name="pagetitle">Roles</x-slot>
    <div class="app-content-header"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Role Management</h3>
                </div>
            </div> <!--end::Row-->
        </div> <!--end::Container-->
    </div>
    <div class="app-content"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-4">
                    <div class="card card-info card-outline mb-4"> <!--begin::Header-->
                        <div class="card-header">
                            <div class="card-title">Roles</div>
                            <div class="card-tools"> 
                                <button type="button" class="btn btn-tool" id="addrole"> 
                                    <i class="bi bi-file-earmark-plus"></i>
                                </button> 
                            </div>
                        </div> <!--end::Header--> <!--begin::Body-->
                        <div class="card-body">
                            <ul class="list-group" id="listrole">
                                @php
                                 $no=0;   
                                @endphp
                                @foreach ($roles as $item)
                                    <li class="list-group-item {{ ($no==0?'active':'') }} d-flex justify-content-between align-items-start" style="cursor: pointer"><span class="rolename">{{ $item->name }}</span> 
                                        @if ($item->name != 'superadmin')
                                        <button class="btn badge bg-danger rounded-pill" onclick="delr(this,'{{ $item->name }}')"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </li>
                                    @php
                                    $no++;   
                                    @endphp
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Permission</div>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach ($permissions as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input chk" data-name="{{ $item->name }}" id="chk{{ $item->id }}" type="checkbox" role="switch"/>
                                        <label class="form-check-label" for="flexSwitchCheckDefault">{{ $item->name }}</label>
                                    </div>
                                    <button class="btn badge bg-danger rounded-pill" onclick="delp(this,'{{ $item->name }}')"><i class="bi bi-trash"></i></button>
                                </li>
                                @endforeach
                            </ul>
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
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1">Name</span>
                        <input type="text" class="form-control" placeholder="" aria-label="Username" aria-describedby="basic-addon1" name="rolename">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saverole">Save changes</button>
            </div>
        </div>
        </div>
    </div>
    <x-slot name="csscustom">
        
    </x-slot>
    <x-slot name="jscustom">
        <script>
            function delr(obj,txtname){
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('roles/delr') }}",
                            method:"DELETE",
                            data: { name: txtname },
                            success: function(response) {$(obj).parent().remove();},
                        }).done(function() {
                            Swal.fire({
                                title: "Deleted!",
                                text: "Your file has been deleted.",
                                icon: "success"
                            });
                        });
                    }
                });
            }
            function delp(obj,txtname){
                Swal.fire({
                    title: "Delete Permission, Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('roles/delp') }}",
                            method:"DELETE",
                            data: { name: txtname },
                            success: function(response) {$(obj).parent().remove();},
                        }).done(function() {
                            Swal.fire({
                                title: "Deleted!",
                                text: "Permission has been deleted.",
                                icon: "success"
                            });
                        });
                    }
                });
            }
            $( document ).ready(function() {
                $('#addrole').on('click',function(){
                    $('#exampleModal').modal('show');
                    $('input[name="rolename"]').val('')
                });
                $('#saverole').on('click',function(){
                    $.ajax({
                        url: "{{ url('roles/add') }}",
                        method:"POST",
                        data: { name: $('input[name="rolename"]').val() },
                        success: function(response) {
                            console.log("Request was successful:", response);
                        },
                    }).done(function() {
                        $('#exampleModal').modal('hide');
                        $('input[name="rolename"]').val('')
                    });
                });
                $('#listrole li').click(function() {
                    let obj=$(this);
                    $.ajax({
                        url: "{{ url('roles/permission') }}",
                        method:"GET",
                        data: { name: obj.find('.rolename').text() },
                        success: function(response) {
                            $('.chk').prop('checked', false);
                            $.each( response, function( key, value ) {
                                $('#chk'+value.id).prop('checked', true);
                            });
                        },
                    }).done(function() {
                        $('#listrole li').removeClass('active'); // Remove active class from all items
                        obj.addClass('active'); // Add active class to the clicked item
                    });
                });
                $('#listrole li:first').click();
                $(".chk").change(function() {
                    let permissionname = $(this).data('name');
                    let ckecked = this.checked
                    $.ajax({
                        url: "{{ route('roles.switch') }}",
                        method:"POST",
                        data: { 
                            permission: permissionname, 
                            role: function() {return $('#listrole li.active').find('.rolename').text();},
                            chk: ckecked
                        },
                        success: function(response) {
                            
                        },
                    });
                    if(this.checked) {
                        console.log($(this).data('name'))
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>