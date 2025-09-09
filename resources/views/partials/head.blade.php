<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="ColorlibHQ">
<meta name="description" content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS.">
<meta name="keywords" content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard"><!--end::Primary Meta Tags--><!--begin::Fonts-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    use App\Models\Setting;
    $setting = Setting::first();
@endphp

<title>{{ $setting->nama_perusahaan ?? '-' }} - {{ !empty($pagetitle) ? $pagetitle : config('app.name', 'Laravel') }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('arisa.ico') }}">
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/overlayscrollbars.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-icons-1.13.1/bootstrap-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/adminlte.css') }}">
<link type="text/css" rel="stylesheet" href="{{ asset('plugins/loader/waitMe.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/DataTables/css/dataTables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/DataTables/css/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/DataTables/button/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/select2-bootstrap-5-theme.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/daterangepicker-master/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/tooltipster/dist/css/tooltipster.bundle.min.css') }}">
<link href="{{ asset('plugins/EasyAutocomplete/dist/easy-autocomplete.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('plugins/EasyAutocomplete/dist/easy-autocomplete.themes.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('plugins/animate.min.css') }}" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="{{ asset('css/tippy.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/webdatarocks-1.4.19/webdatarocks.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/jstree/themes/default/style.min.css') }}">
<style>
    .submenu {
        display: none;
    }

    .nav-item.menu-item .nav-link.active {
        background-color: #0d6efd;
        color: #fff;
    }
    
    .label-fixed-width {
        min-width: 100px; /* Sesuaikan dengan panjang label terpanjang */
        text-align: right;
        justify-content: flex-end;
    }
    .tt-menu {
        width: 100%;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        z-index: 1000;
        max-height: 250px;
        overflow-y: auto;
    }
    .tt-suggestion {
        padding: 0.5rem 1rem;
        cursor: pointer;
    }
    .tt-suggestion:hover {
        background-color: #f8f9fa;
    }
</style>