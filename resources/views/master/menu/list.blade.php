<x-app-layout>
    <x-slot name="pagetitle">Roles</x-slot>
    <div class="app-content-header"> <!--begin::Container-->
        <div class="container-fluid"> <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Menu Management</h3>
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
                                    <li class="list-group-item {{ ($no==0?'active':'') }} d-flex justify-content-between align-items-start" style="cursor: pointer" onClick="treajax('{{ $item->name }}')">
                                        <span class="rolename">{{ $item->name }}</span> 
                                    </li>
                                    @php
                                    $no++;   
                                    @endphp
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">Menu</div>
                        </div>
                        <div class="card-body">
                            <div id="jstree_demo_div"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-slot name="csscustom">
    </x-slot>
    <x-slot name="jscustom">
        <script>
            function treajax(group){
                $('#jstree_demo_div').jstree("destroy").empty();
                $('#jstree_demo_div').jstree({
                    'core' : {
                    'data' : {
                        'url' : '{{ url('menu/data/') }}/'+group,
                        'data' : function (node) {
                        return { 'id' : node.id };
                        }
                    }
                    },
                    "plugins" : [ "checkbox" ]
                });
                $("#jstree_demo_div").bind("changed.jstree",
                    function (e, data) {
                        $.ajax({
                        method: "PUT",
                        url: "{{ route('menu.update') }}",
                        data: { id: data.node.id, aktif: data.node.state.selected ,gp:group }
                        })
                    });
                }
            $( document ).ready(function() {
                $('#jstree_demo_div').jstree("destroy").empty();
                $('#listrole li').click(function() {
                    let obj=$(this);
                    $('#listrole li').removeClass('active');
                    obj.addClass('active'); 
                });
                $('#listrole li:first').click();
                
            });
        </script>
    </x-slot>
</x-app-layout>