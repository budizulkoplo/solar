<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// Controllers
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserProjectController;
use App\Http\Controllers\RekeningController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitDetailController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProjectSelectionController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\HRISController;
use App\Http\Controllers\UnitKerjaController;
use App\Http\Controllers\PlottingUnitKerjaController;
use App\Http\Controllers\KelompokJamController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\PengajuanIzinController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\MasterGajiController;
use App\Http\Controllers\KodetransaksiController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PTController;
use App\Http\Controllers\PendingPiutangController;
use App\Http\Controllers\CompanyPendingPiutangController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PindahBukuController;
use App\Http\Controllers\PembiayaanController;
use App\Http\Controllers\AgencySaleController;
use App\Http\Controllers\PekerjaanKonstruksiController;
use App\Http\Controllers\ConstructionTransactionController;
use App\Http\Controllers\AssetTransactionController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\PencairanBankController;
use App\Http\Controllers\PenjualanPaymentController;

// Mobile
use App\Http\Controllers\Mobile\DashboardController;
use App\Http\Controllers\Mobile\MobileProjectController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\PresensiController;
use App\Http\Controllers\Mobile\KalenderController;
use App\Http\Controllers\Mobile\MobileBonusController;
/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

// Default redirect ke login
Route::get('/', fn () => redirect()->route('login'));

// Login
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Pilih Project (akses tanpa middleware check.project)
Route::middleware(['auth', 'verified', 'global.app'])->group(function () {
    Route::get('/choose-project', [ProjectSelectionController::class, 'index'])->name('choose.project');
    Route::post('/choose-project', [ProjectSelectionController::class, 'store'])->name('choose.project.store');
});

// Semua route ini wajib: auth + verified + project sudah dipilih
Route::middleware(['auth', 'verified', 'check.project'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])
        ->middleware('global.app:admin')
        ->name('dashboard');
    Route::get('/admin/pesanan-hari-ini', [AdminDashboardController::class, 'pesananHariIni']);
    Route::get('/admin/data-pesanan-hari-ini', [AdminDashboardController::class, 'pesananHariIniData'])
        ->name('dashboard.pesananHariIniData');

    // Profile
    Route::prefix('profile')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/', [ProfileController::class, 'upload'])->name('profile.upload');
    });

    // Users
    Route::prefix('users')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/list', [UsersController::class, 'index'])->name('users.list');
        Route::get('/getdata', [UsersController::class, 'getdata'])->name('users.getdata');
        Route::post('/assignRole', [UsersController::class, 'kasihRole'])->name('users.assignRole');
        Route::post('/password/update', [UsersController::class, 'updatePassword'])->name('users.updatepassword');
        Route::post('/store', [UsersController::class, 'store'])->name('users.store');
        Route::get('/getcode', [UsersController::class, 'getcode'])->name('users.getcode');

        // Role management
        Route::get('/permission', [UserRoleController::class, 'PermissionByRole']);
        Route::post('/add', [UserRoleController::class, 'addRole']);
        Route::delete('/delr', [UserRoleController::class, 'deleteRole']);
        Route::delete('/delp', [UserRoleController::class, 'deletePermission']);
    });

    // Pegawai
    Route::prefix('pegawai')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/list', [PegawaiController::class, 'index'])->name('pegawai.list');
        Route::get('/getdata', [PegawaiController::class, 'getdata'])->name('pegawai.getdata');
        Route::post('/store', [PegawaiController::class, 'store'])->name('pegawai.store');
        Route::get('/getcode', [PegawaiController::class, 'getcode'])->name('pegawai.getcode');
        Route::get('/{id}', [PegawaiController::class, 'show'])->name('pegawai.show');   // untuk edit (ambil data 1 pegawai)
        Route::delete('/{id}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy'); // untuk hapus
    });

    Route::prefix('master')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/unitkerja', [UnitKerjaController::class, 'index'])->name('master.unitkerja');
        Route::get('/unitkerja/data', [UnitKerjaController::class, 'getdata'])->name('master.unitkerja.data');
        Route::get('/unitkerja/{id}', [UnitKerjaController::class, 'show'])->name('master.unitkerja.show');
        Route::post('/unitkerja', [UnitKerjaController::class, 'store'])->name('master.unitkerja.store');
        Route::delete('/unitkerja/{id}', [UnitKerjaController::class, 'destroy'])->name('master.unitkerja.destroy');

        Route::post('/unitkerja/togglelock', [UnitKerjaController::class, 'toggleLock'])->name('master.unitkerja.togglelock');
    });

    Route::prefix('master')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/plotting-unitkerja', [PlottingUnitKerjaController::class, 'index'])->name('plotting.unitkerja');
        Route::get('/plotting-unitkerja/data', [PlottingUnitKerjaController::class, 'getdata'])->name('plotting.unitkerja.data');
        Route::post('/plotting-unitkerja/update', [PlottingUnitKerjaController::class, 'updateUnit'])->name('plotting.unitkerja.update');
    });

    Route::prefix('master')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('kelompokjam', [KelompokJamController::class, 'index'])->name('master.kelompokjam');
        Route::get('kelompokjam/data', [KelompokJamController::class, 'getdata'])->name('master.kelompokjam.data');
        Route::post('kelompokjam/store', [KelompokJamController::class, 'store'])->name('master.kelompokjam.store');
        Route::get('kelompokjam/{id}', [KelompokJamController::class, 'show']);
        Route::delete('kelompokjam/{id}', [KelompokJamController::class, 'destroy']);
    });

    Route::prefix('master')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('jadwal', [JadwalController::class, 'index'])->name('master.jadwal');
        Route::get('jadwal/pegawai', [JadwalController::class, 'getPegawai'])->name('master.jadwal.pegawai');
        Route::post('jadwal/update', [JadwalController::class, 'updateShift'])->name('master.jadwal.update');
        Route::post('jadwal/generate', [JadwalController::class, 'generateOtomatis'])->name('master.jadwal.generate');
    });

    Route::prefix('master')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('gaji', [MasterGajiController::class, 'index'])->name('master.gaji');
        Route::get('gaji/pegawai', [MasterGajiController::class, 'getPegawai'])->name('master.gaji.pegawai');
        Route::get('gaji/riwayat/{nik}', [MasterGajiController::class, 'riwayat'])->name('master.gaji.riwayat');
        Route::post('gaji/store', [MasterGajiController::class, 'store'])->name('master.gaji.store');
        Route::delete('gaji/{id}', [MasterGajiController::class, 'destroy'])->name('master.gaji.destroy');
        Route::put('gaji/{id}', [MasterGajiController::class, 'update'])->name('master.gaji.update');
    });

    Route::prefix('hris')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/absensi', [AbsensiController::class, 'index'])->name('hris.absensi');
        Route::get('/absensi/getdata', [AbsensiController::class, 'getAbsensiData'])->name('hris.absensi.getdata');
        Route::get('pengajuan-izin', [PengajuanIzinController::class, 'index'])->name('hris.pengajuanizin');
        Route::get('pengajuan-izin/data', [PengajuanIzinController::class, 'getdata'])->name('hris.pengajuanizin.data');
        Route::get('pengajuan-izin/show/{id}', [PengajuanIzinController::class, 'show'])->name('hris.pengajuanizin.show');
        Route::post('pengajuan-izin/store', [PengajuanIzinController::class, 'store'])->name('hris.pengajuanizin.store');
        Route::delete('pengajuan-izin/{id}', [PengajuanIzinController::class, 'destroy'])->name('hris.pengajuanizin.destroy');
        Route::get('pengajuan-izin/select2/pegawai', [PengajuanIzinController::class, 'getPegawaiSelect2'])->name('hris.pengajuanizin.select2pegawai');
        
        // Laporan Rekap Absensi
        Route::get('laporan/rekap-absensi', [LaporanController::class, 'rekapAbsensi'])->name('hris.laporan.rekap_absensi');
        Route::get('laporan/rekap-absensi/data', [LaporanController::class, 'rekapAbsensiData'])->name('hris.laporan.rekap_absensi.data');
        Route::post('laporan/rekap-absensi/export-payroll', [LaporanController::class, 'exportPayroll'])->name('hris.laporan.rekap_absensi.export_payroll');
        // Laporan Payroll
        Route::get('laporan/payroll', [LaporanController::class, 'laporanPayroll'])->name('hris.laporan.payroll');
        Route::get('laporan/payroll/data', [LaporanController::class, 'laporanPayrollData'])->name('hris.laporan.payroll.data');
        // Laporan Monitoring Presensi
        Route::get('laporan/monitoring-presensi', [LaporanController::class, 'monitoringPresensi'])->name('hris.laporan.monitoring_presensi');
        Route::get('laporan/monitoring-presensi/data', [LaporanController::class, 'monitoringPresensiData'])->name('hris.laporan.monitoring_presensi.data');
        Route::post('laporan/monitoring-presensi/export', [LaporanController::class, 'exportMonitoringPresensi'])->name('hris.laporan.monitoring_presensi.export');
        // Laporan Cashflow
        Route::get('laporan/cashflow-project', [LaporanController::class, 'cashflowProject'])->name('transaksi.laporan.cashflow_project');
        Route::get('laporan/cashflow-project/data', [LaporanController::class, 'cashflowProjectData'])->name('transaksi.laporan.cashflow_project.data');
        Route::get('laporan/cashflow-pt', [LaporanController::class, 'cashflowPT'])->name('transaksi.laporan.cashflow_pt');
        Route::get('laporan/cashflow-pt/data', [LaporanController::class, 'cashflowPTData'])->name('transaksi.laporan.cashflow_pt.data');

        Route::get('laporan/cashflow-project/view-nota', [LaporanController::class, 'viewNotaDetail'])->name('transaksi.laporan.cashflow_project.view_nota');
        Route::get('laporan/cashflow-pt/view-nota', [LaporanController::class, 'viewNotaDetailPT'])->name('transaksi.laporan.cashflow_pt.view_nota');

        // Laporan Presensi Visit
        Route::get('laporan/rekap-visit', [LaporanController::class, 'rekapVisit'])->name('hris.laporan.rekap_visit');
        Route::get('laporan/rekap-visit/data', [LaporanController::class, 'rekapVisitData'])->name('hris.laporan.rekap_visit.data');
        Route::get('laporan/monitoring-visit', [LaporanController::class, 'monitoringVisit'])->name('hris.laporan.monitoring_visit');
        Route::get('laporan/monitoring-visit/data', [LaporanController::class, 'monitoringVisitData'])->name('hris.laporan.monitoring_visit.data');
        Route::get('laporan/detail-visit', [LaporanController::class, 'detailVisit'])->name('hris.laporan.detail_visit');
        Route::get('laporan/view-foto-visit', [LaporanController::class, 'viewFotoVisit'])->name('hris.laporan.view_foto_visit');
        Route::get('laporan/export-visit-excel', [LaporanController::class, 'exportVisitExcel'])->name('hris.laporan.export_visit_excel');

        // === Payroll (Tabel Gaji) ===
        Route::get('payroll', [PayrollController::class, 'index'])->name('hris.payroll.index');
        Route::get('payroll/data', [PayrollController::class, 'getData'])->name('hris.payroll.data');
        Route::post('payroll/update', [PayrollController::class, 'updateManual'])->name('hris.payroll.update_manual');
        Route::get('payroll/slip/{payroll_id}', [PayrollController::class, 'downloadSlip'])->name('hris.payroll.slip');

        // === Bonus (Tabel Bonus) ===
        Route::get('bonus', [BonusController::class, 'index'])->name('hris.bonus.index');
        Route::get('bonus/data', [BonusController::class, 'getData'])->name('hris.bonus.data');
        Route::post('bonus', [BonusController::class, 'store'])->name('hris.bonus.store');
        Route::put('bonus/{id}', [BonusController::class, 'update'])->name('hris.bonus.update');
        Route::delete('bonus/{id}', [BonusController::class, 'destroy'])->name('hris.bonus.destroy');
        Route::get('bonus/slip/{nik}/{periode}', [BonusController::class, 'downloadSlip'])->name('hris.bonus.slip');
        Route::get('bonus/user-bonus', [BonusController::class, 'getBonusByUser'])->name('hris.bonus.user_bonus');

    });

    // Laporan
    Route::prefix('laporan')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/bookings', [LaporanController::class, 'bookings'])->name('laporan.bookings');
        Route::get('/penjualan', [LaporanController::class, 'penjualan'])->name('laporan.penjualan');
        Route::get('/export-bookings-pdf', [LaporanController::class, 'exportBookingsPDF'])->name('laporan.export.bookings.pdf');
        Route::get('/export-penjualan-pdf', [LaporanController::class, 'exportPenjualanPDF'])->name('laporan.export.penjualan.pdf');
        Route::get('/statistics', [LaporanController::class, 'getStatistics'])->name('laporan.statistics');

        Route::get('/neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('laporan.neraca-saldo');
        Route::get('/neraca-saldo/data', [LaporanController::class, 'neracaSaldoData'])->name('laporan.neraca-saldo.data');
        Route::post('/neraca-saldo/export-excel', [LaporanController::class, 'exportNeracaSaldoExcel'])->name('laporan.neraca-saldo.export-excel');
        Route::post('/neraca-saldo/print', [LaporanController::class, 'printNeracaSaldo'])->name('laporan.neraca-saldo.print');
    });

    // Companies
    Route::prefix('companies')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/store', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::post('/projects/store', [CompanyController::class, 'storeProject'])->name('companies.projects.store');
        Route::delete('/projects/{id}', [CompanyController::class, 'destroyProject'])->name('companies.projects.destroy');
        Route::get('/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::get('/projects/{id}/edit', [CompanyController::class, 'editProject'])->name('companies.projects.edit');
    });

    Route::prefix('coas')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [CoaController::class, 'index'])->name('coas.index');
        Route::get('/getdata', [CoaController::class, 'getData'])->name('coas.getdata');
        Route::post('/store', [CoaController::class, 'store'])->name('coas.store');
        Route::get('/{coa}', [CoaController::class, 'show'])->name('coas.show');
        Route::put('/{coa}', [CoaController::class, 'update'])->name('coas.update');
        Route::delete('/{coa}', [CoaController::class, 'destroy'])->name('coas.destroy');
    });

    Route::prefix('kodetransaksi')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [KodetransaksiController::class,'index'])->name('kodetransaksi.index');
        Route::get('/data', [KodetransaksiController::class,'getData'])->name('kodetransaksi.data');
        Route::post('/store', [KodetransaksiController::class,'store'])->name('kodetransaksi.store');
        Route::get('/{id}/edit', [KodetransaksiController::class,'edit'])->name('kodetransaksi.edit');
        Route::put('/{id}', [KodetransaksiController::class,'update'])->name('kodetransaksi.update');
        Route::delete('/{id}', [KodetransaksiController::class,'destroy'])->name('kodetransaksi.destroy');
        Route::patch('/{id}/update-field', [KodetransaksiController::class,'updateField'])->name('kodetransaksi.updateField');
        Route::get('/export/excel', [KodetransaksiController::class, 'exportExcel'])->name('kodetransaksi.export.excel');
    });

    Route::prefix('transaksi')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {

        // === TRANSAKSI PROJECT ===
        Route::prefix('project')->group(function() {
            // halaman list
            Route::get('in',  [ProjectController::class,'in'])->name('transaksi.project.in');
            Route::get('out', [ProjectController::class,'out'])->name('transaksi.project.out');

            // datatables
            Route::get('getdata/{type}', [ProjectController::class,'getdata'])->name('transaksi.project.getdata');

            // CRUD operations
            Route::post('store/{type}', [ProjectController::class,'store'])->name('transaksi.project.store');
            Route::get('{id}', [ProjectController::class,'show'])->name('transaksi.project.show');
            Route::get('{id}/edit', [ProjectController::class,'edit'])->name('transaksi.project.edit');
            Route::put('{id}/{type}', [ProjectController::class,'update'])->name('transaksi.project.update');
            Route::delete('{id}', [ProjectController::class,'destroy'])->name('transaksi.project.destroy');
            Route::post('{id}/status', [ProjectController::class,'updateStatus'])->name('transaksi.project.status');

            // ambil saldo rekening
            Route::get('rekening/{id}/saldo', [ProjectController::class,'saldoRekening'])->name('transaksi.project.rekening.saldo');
            
            // get update logs
            Route::get('{id}/logs', [ProjectController::class,'getUpdateLogs'])->name('transaksi.project.logs');
        });

        // === TRANSAKSI PT (INDUK) ===
        Route::prefix('pt')->group(function() {
            // halaman list
            Route::get('in',  [PTController::class,'in'])->name('transaksi.pt.in');
            Route::get('out', [PTController::class,'out'])->name('transaksi.pt.out');

            // datatables
            Route::get('getdata/{type}', [PTController::class,'getdata'])->name('transaksi.pt.getdata');

            // CRUD operations
            Route::post('store/{type}', [PTController::class,'store'])->name('transaksi.pt.store');
            Route::get('{id}', [PTController::class,'show'])->name('transaksi.pt.show');
            Route::get('{id}/edit', [PTController::class,'edit'])->name('transaksi.pt.edit');
            Route::put('{id}/{type}', [PTController::class,'update'])->name('transaksi.pt.update');
            Route::delete('{id}', [PTController::class,'destroy'])->name('transaksi.pt.destroy');
            Route::post('{id}/status', [PTController::class,'updateStatus'])->name('transaksi.pt.status');

            // ambil saldo rekening
            Route::get('rekening/{id}/saldo', [PTController::class,'saldoRekening'])->name('transaksi.pt.rekening.saldo');
            // get update logs
            Route::get('{id}/logs', [PTController::class,'getUpdateLogs'])->name('transaksi.pt.logs');
        });

        // === TRANSAKSI PINDAH BUKU ===
        Route::prefix('pindah-buku')->group(function() {
            // halaman list
            Route::get('/',  [PindahBukuController::class,'index'])->name('transaksi.pindahbuku.index');

            // datatables
            Route::get('getdata', [PindahBukuController::class,'getdata'])->name('transaksi.pindahbuku.getdata');

            // CRUD operations
            Route::post('store', [PindahBukuController::class,'store'])->name('transaksi.pindahbuku.store');
            Route::get('{id}', [PindahBukuController::class,'show'])->name('transaksi.pindahbuku.show');
            Route::get('{id}/edit', [PindahBukuController::class,'edit'])->name('transaksi.pindahbuku.edit');
            Route::put('{id}', [PindahBukuController::class,'update'])->name('transaksi.pindahbuku.update');
            Route::delete('{id}', [PindahBukuController::class,'destroy'])->name('transaksi.pindahbuku.destroy');

            // ambil saldo rekening
            Route::get('rekening/{id}/saldo', [PindahBukuController::class,'saldoRekening'])
                ->name('transaksi.pindahbuku.rekening.saldo');
        });

        // === TRANSAKSI ASSET ===
        Route::prefix('asset')->group(function() {
            Route::get('/', [AssetTransactionController::class,'index'])->name('transaksi.asset.index');
            Route::get('create', [AssetTransactionController::class,'create'])->name('transaksi.asset.create');
            Route::post('store', [AssetTransactionController::class,'store'])->name('transaksi.asset.store');
            Route::get('getdata', [AssetTransactionController::class,'getdata'])->name('transaksi.asset.getdata');
            Route::post('{id}/generate', [AssetTransactionController::class,'generateAssetFromNota'])->name('transaksi.asset.generate');
            Route::get('{id}/assets', [AssetTransactionController::class,'getAssetDetails'])->name('transaksi.asset.details');
            Route::get('list', [AssetTransactionController::class,'assetList'])->name('transaksi.asset.list');
            Route::get('list/getdata', [AssetTransactionController::class,'getAssetData'])->name('transaksi.asset.list.getdata');
            Route::post('generate-depreciation', [AssetTransactionController::class,'generateMonthlyDepreciation'])->name('transaksi.asset.generate.depreciation');
            Route::get('export', [AssetTransactionController::class,'exportAssets'])->name('transaksi.asset.export');
        });

        // === PEMBIAYAAN ===
        Route::prefix('pembiayaan')->group(function() {
            Route::get('/', [PembiayaanController::class, 'index'])
                ->name('transaksi.pembiayaan.index');

            // /pembiayaan/company | /pembiayaan/project
            Route::get('/{type}', [PembiayaanController::class, 'index'])
                ->whereIn('type', ['company', 'project'])
                ->name('transaksi.pembiayaan.type');
            
            // datatables
            Route::get('getdata/{type}', [PembiayaanController::class,'getdata'])
                ->name('transaksi.pembiayaan.getdata');

            // CRUD operations
            Route::get('create/{type}', [PembiayaanController::class,'create'])
                ->name('transaksi.pembiayaan.create');
            Route::post('store', [PembiayaanController::class,'store'])
                ->name('transaksi.pembiayaan.store');
            Route::get('{id}', [PembiayaanController::class,'show'])
                ->name('transaksi.pembiayaan.show');
            Route::get('{id}/edit', [PembiayaanController::class,'edit'])
                ->name('transaksi.pembiayaan.edit');
            Route::put('{id}', [PembiayaanController::class,'update'])
                ->name('transaksi.pembiayaan.update');
            Route::delete('{id}', [PembiayaanController::class,'destroy'])
                ->name('transaksi.pembiayaan.destroy');

            // Setoran operations
            Route::get('{id}/setoran', [PembiayaanController::class,'getSetoran'])
                ->name('transaksi.pembiayaan.setoran.get');
            Route::post('{id}/setoran', [PembiayaanController::class,'storeSetoran'])
                ->name('transaksi.pembiayaan.setoran.store');
            Route::delete('{id}/setoran/{setoranId}', [PembiayaanController::class,'deleteSetoran'])
                ->name('transaksi.pembiayaan.setoran.delete');

            // utilities
            Route::get('rekening/{id}/saldo', [PembiayaanController::class,'saldoRekening'])
                ->name('transaksi.pembiayaan.rekening.saldo');
            Route::get('get-project-session', [PembiayaanController::class,'getProjectFromSession'])
                ->name('transaksi.pembiayaan.project.session');
        });

    });

    // Agency Sales Routes
    Route::prefix('agency-sales')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->name('agency-sales.')
    ->group(function () {
        Route::get('/', [AgencySaleController::class, 'index'])->name('index');
        Route::get('/data', [AgencySaleController::class, 'getData'])->name('get-data');
        Route::get('/create/{unitDetailId}', [AgencySaleController::class, 'create'])->name('create');
        Route::post('/', [AgencySaleController::class, 'store'])->name('store');

        Route::get('/{id}/edit', [AgencySaleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AgencySaleController::class, 'update'])->name('update');
        Route::delete('/{id}', [AgencySaleController::class, 'destroy'])->name('destroy');
        Route::get('/transactions/data', [AgencySaleController::class, 'getTransactions'])->name('transactions.data');
        Route::get('/{id}', [AgencySaleController::class, 'show'])->name('show');
    });

    // routes/web.php
    Route::prefix('pekerjaan-konstruksi')
        ->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])
        ->name('pekerjaan-konstruksi.')
        ->group(function () {
            Route::get('/', [PekerjaanKonstruksiController::class, 'index'])->name('index');
            Route::get('/data', [PekerjaanKonstruksiController::class, 'getData'])->name('get-data');
            Route::post('/', [PekerjaanKonstruksiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PekerjaanKonstruksiController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PekerjaanKonstruksiController::class, 'update'])->name('update');
            Route::delete('/{id}', [PekerjaanKonstruksiController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/update-status', [PekerjaanKonstruksiController::class, 'updateStatus'])->name('update-status');
            Route::get('/by-project/{projectId}', [PekerjaanKonstruksiController::class, 'getByProject'])->name('by-project');
            Route::get('/report', [PekerjaanKonstruksiController::class, 'report'])->name('report');
            // PERBAIKAN: Hapus '.pekerjaan-konstruksi' dari nama route
            Route::get('/{id}', [PekerjaanKonstruksiController::class, 'show'])->name('show'); // ← Nama yang benar
            
        });

    Route::prefix('construction')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->name('construction.')->group(function () {
        Route::get('/progress', [PekerjaanKonstruksiController::class, 'progressIndex'])->name('progress.index');
        Route::get('/progress/data', [PekerjaanKonstruksiController::class, 'getProgressData'])->name('progress.data');
        Route::get('/progress/{id}/detail', [PekerjaanKonstruksiController::class, 'getProgressDetail'])->name('progress.detail');
        Route::put('/progress/{id}/update', [PekerjaanKonstruksiController::class, 'updateProgress'])->name('progress.update');
        Route::get('/progress/{id}/logs', [PekerjaanKonstruksiController::class, 'getProgressLogs'])->name('progress.logs');
        Route::put('/progress/{id}/start', [PekerjaanKonstruksiController::class, 'startProgress'])->name('progress.start');
    });

    // Construction Transactions Routes
    Route::prefix('construction/transactions')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->name('construction.transactions.')->group(function () {
        Route::get('/', [ConstructionTransactionController::class, 'index'])->name('index');
        Route::get('/{pekerjaanId}', [ConstructionTransactionController::class, 'showTransactions'])->name('detail');
        Route::get('/{pekerjaanId}/data', [ConstructionTransactionController::class, 'getTransactionsData'])->name('data');
        Route::get('/{pekerjaanId}/create', [ConstructionTransactionController::class, 'create'])->name('create');
        Route::post('/{pekerjaanId}/store', [ConstructionTransactionController::class, 'store'])->name('store');
        Route::get('/{pekerjaanId}/edit/{notaId}', [ConstructionTransactionController::class, 'edit'])->name('edit');
        Route::put('/{pekerjaanId}/update/{notaId}', [ConstructionTransactionController::class, 'update'])->name('update');
        Route::get('/{pekerjaanId}/show/{notaId}', [ConstructionTransactionController::class, 'show'])->name('show');
        Route::delete('/{pekerjaanId}/delete/{notaId}', [ConstructionTransactionController::class, 'destroy'])->name('destroy');
        Route::put('/{pekerjaanId}/status/{notaId}', [ConstructionTransactionController::class, 'updateStatus'])->name('status');
        Route::get('/{pekerjaanId}/logs/{notaId}', [ConstructionTransactionController::class, 'getUpdateLogs'])->name('logs');
        Route::get('/rekening/{id}/saldo', [ConstructionTransactionController::class, 'saldoRekening'])->name('rekening.saldo');

        Route::get('/{pekerjaanId}/report', [ConstructionTransactionController::class, 'getReport'])->name('report');
    });

    // Routes untuk pending pembayaran dan piutang
    Route::prefix('pending')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {

        Route::get('/pembayaran', [PendingPiutangController::class, 'pendingPembayaran'])->name('pending.pembayaran');
        Route::get('/piutang', [PendingPiutangController::class, 'piutang'])->name('pending.piutang');
        
        // DataTables
        Route::get('/data/pembayaran', [PendingPiutangController::class, 'getPendingPembayaran'])->name('pending.data.pembayaran');
        Route::get('/data/piutang', [PendingPiutangController::class, 'getPiutang'])->name('pending.data.piutang');
        
        // Common operations
        Route::get('/show/{id}', [PendingPiutangController::class, 'show'])->name('pending.show');
        Route::post('/bayar/{id}', [PendingPiutangController::class, 'bayar'])->name('pending.bayar');
        Route::get('/angsuran/{id}', [PendingPiutangController::class, 'getAngsuranHistory'])->name('pending.angsuran');
        Route::delete('/angsuran/{id}', [PendingPiutangController::class, 'hapusAngsuran'])->name('pending.hapus-angsuran');
        
        // Export
        Route::get('/export/{type}', [PendingPiutangController::class, 'exportReport'])->name('pending.export');
    });

    // Routes untuk pending pembayaran dan piutang company
    Route::prefix('company/pending')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/pembayaran', [CompanyPendingPiutangController::class, 'pendingPembayaranCompany'])->name('company.pending.pembayaran');
        Route::get('/piutang', [CompanyPendingPiutangController::class, 'piutangCompany'])->name('company.pending.piutang');
        
        // DataTables
        Route::get('/data/pembayaran', [CompanyPendingPiutangController::class, 'getPendingPembayaranCompany'])->name('company.pending.data.pembayaran');
        Route::get('/data/piutang', [CompanyPendingPiutangController::class, 'getPiutangCompany'])->name('company.pending.data.piutang');
        
        // Common operations
        Route::get('/show/{id}', [CompanyPendingPiutangController::class, 'showCompany'])->name('company.pending.show');
        Route::post('/bayar/{id}', [CompanyPendingPiutangController::class, 'bayarCompany'])->name('company.pending.bayar');
        Route::get('/angsuran/{id}', [CompanyPendingPiutangController::class, 'getAngsuranHistoryCompany'])->name('company.pending.angsuran');
        Route::delete('/angsuran/{id}', [CompanyPendingPiutangController::class, 'hapusAngsuranCompany'])->name('company.pending.hapus-angsuran');
        
        // Export
        Route::get('/export/{type}', [CompanyPendingPiutangController::class, 'exportReportCompany'])->name('company.pending.export');
    });
                
    Route::get('/rekening/{id}/saldo', [RekeningController::class,'getSaldo']);

    // Rekening
    Route::prefix('rekening')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [RekeningController::class, 'index'])->name('rekening.index');
        Route::post('/store', [RekeningController::class, 'store'])->name('rekening.store');
        Route::get('/{id}/edit', [RekeningController::class, 'edit'])->name('rekening.edit');
        Route::delete('/{id}', [RekeningController::class, 'destroy'])->name('rekening.destroy');
    });

    Route::prefix('vendors')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing','global.app'])->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/getdata', [VendorController::class, 'getData'])->name('vendors.getdata');
        Route::post('/store', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/{id}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::delete('/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        Route::post('/{id}/update-jenis', [VendorController::class, 'updateJenis'])->name('vendors.updateJenis');
    });

    // User Projects
    Route::prefix('user-projects')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [UserProjectController::class, 'index'])->name('user-projects.index');
        Route::post('/toggle', [UserProjectController::class, 'toggle'])->name('user-projects.toggle');
        Route::get('/{userId}', [UserProjectController::class, 'getUserProjects']);
    });

    // Units
    Route::prefix('units')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('units.index');
        Route::get('/getdata', [UnitController::class, 'getData'])->name('units.getdata');
        Route::post('/store', [UnitController::class, 'store'])->name('units.store');
        Route::get('/{id}/edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::put('/{id}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/{id}', [UnitController::class, 'destroy'])->name('units.destroy');
        
        // Unit Details routes
        Route::get('/details', [UnitDetailController::class, 'index'])->name('units.details.index');
        Route::put('/details/{id}/status', [UnitDetailController::class, 'updateStatus'])->name('units.details.status');
        Route::get('/details/{id}/detail', [UnitDetailController::class, 'getDetail'])
        ->name('units.details.detail');

        Route::get('/details/statistics', [UnitDetailController::class, 'getStatistics'])->name('units.details.statistics');
    });

    // Customers
    Route::prefix('customers')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/getdata', [CustomerController::class, 'getData'])->name('customers.getdata');
        Route::post('/store', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::get('/{id}/detail', [CustomerController::class, 'getDetail'])->name('customers.detail');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // Setting
    Route::prefix('setting')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('setting.index');
        Route::post('/', [SettingController::class, 'update'])->name('setting.update');
    });

    // Roles
    Route::prefix('roles')->middleware(['role:superadmin', 'global.app'])->group(function () {
        Route::get('/list', [UserRoleController::class, 'index'])->name('roles.list');
        Route::get('/permission', [UserRoleController::class, 'PermissionByRole']);
        Route::post('/add', [UserRoleController::class, 'addRole']);
        Route::delete('/delr', [UserRoleController::class, 'deleteRole']);
        Route::delete('/delp', [UserRoleController::class, 'deletePermission']);
        Route::post('/swcp', [UserRoleController::class, 'PermissionfromRole'])->name('roles.switch');
    });

    // Menu
    Route::prefix('menu')->middleware(['role:superadmin', 'global.app'])->group(function () {
        Route::get('/list', [MenuController::class, 'index'])->name('menu.list');
        Route::get('/data/{role}', [MenuController::class, 'datamenu'])->name('menu.data');
        Route::put('/update', [MenuController::class, 'update'])->name('menu.update');
    });

    // Static file (private doc/img)
    Route::prefix('doc')->group(function () {
        Route::get('download/{filename}', function ($filename) {
            if (!Auth::check()) abort(403);
            $path = storage_path("app/private/doc/{$filename}");
            if (!file_exists($path)) abort(404);
            return Response::download($path);
        });
        Route::get('file/{path}/{filename}', function ($path, $filename) {
            if (!Auth::check()) abort(403);
            $path = storage_path("app/private/img/{$path}/{$filename}");
            if (!File::exists($path)) abort(404);
            $file = File::get($path);
            $type = File::mimeType($path);
            return Response::make($file, 200)->header("Content-Type", $type);
        });
    });
});

// UI untuk mobile end users
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
});

Route::middleware(['auth'])->prefix('mobile/presensi')->name('mobile.presensi.')->group(function () {
    Route::get('/create', [PresensiController::class, 'create'])->name('create');
    Route::post('/store', [PresensiController::class, 'store'])->name('store');
    Route::get('/lembur', [PresensiController::class, 'lembur'])->name('lembur');
    Route::post('/cek-radius', [PresensiController::class, 'cekRadius'])->name('mobile.presensi.cekRadius');
    Route::get('/get-unitkerja-location', [PresensiController::class, 'getUnitKerjaLocation'])
            ->name('getUnitKerjaLocation');
    Route::get('/visit', [PresensiController::class, 'visit'])->name('visit');
    Route::post('/store-visit', [PresensiController::class, 'storeVisit'])->name('storeVisit');
    Route::get('/histori-visit', [PresensiController::class, 'historiVisit'])->name('historiVisit');
    Route::get('/gethistori-visit', [PresensiController::class, 'gethistoriVisit'])->name('gethistoriVisit');

    //Izin
    Route::get('/izin', [PresensiController::class, 'izin']);
    Route::get('/buatizin', [PresensiController::class, 'buatizin']);
    Route::post('/storeizin', [PresensiController::class, 'storeizin']);
    Route::post('/cekpengajuanizin', [PresensiController::class, 'cekpengajuanizin']);

    // Approval Izin/Sakit/Cuti
    Route::get('/approvalizin', [PresensiController::class, 'approvalizin']);
    Route::post('/approvedizin', [PresensiController::class, 'approvedizin']);
    Route::post('/batalkanizin/{id}', [PresensiController::class, 'batalkanizin']);
    Route::delete('/hapusizin/{id}', [PresensiController::class, 'hapusizin']);

    //Edit Profile
    Route::get('/editprofile', [PresensiController::class, 'editprofile']);
    Route::post('{nik}/updateprofile', [PresensiController::class, 'updateprofile']);

    //Histori
    Route::get('/histori', [PresensiController::class, 'histori']);
    Route::post('/gethistori', [PresensiController::class, 'gethistori']);

    Route::get('/agenda', [PresensiController::class, 'agendaForm'])->name('agenda');
    Route::post('/agenda', [PresensiController::class, 'storeAgenda'])->name('agenda.store');
    Route::get('/agenda/list', [PresensiController::class, 'listAgenda'])->name('agenda.list');
    Route::delete('/agenda/{id}/delete', [PresensiController::class, 'deleteAgenda'])->name('agenda.delete');

});

Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    // Dashboard
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    Route::get('/dashboard/agenda', [DashboardController::class, 'agendaList'])
        ->name('dashboard.agenda');
    
    // Route untuk delete agenda
    Route::delete('/agenda/{id}', [PresensiController::class, 'deleteAgenda'])
        ->name('presensi.agenda.delete');

    // Di routes/web.php
    Route::get('/dashboard/tempo-data', [DashboardController::class, 'getTempoData'])->name('dashboard.tempo_data');
    
    // Modul Kalender
    Route::prefix('kalender')->name('kalender.')->group(function () {
        Route::get('/', [KalenderController::class, 'index'])->name('index'); // Tampilan utama kalender
        Route::post('/', [KalenderController::class, 'index']);
        Route::get('/lembur', [KalenderController::class, 'lembur'])->name('lembur'); // Halaman lembur
        Route::get('/statistik', [KalenderController::class, 'statistik'])->name('statistik'); // Statistik per bulan
    });

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [App\Http\Controllers\Mobile\MobilePayrollController::class, 'index'])->name('index');
        Route::get('/{tahun}/{bulan}', [App\Http\Controllers\Mobile\MobilePayrollController::class, 'detail'])->name('detail');
        Route::get('/download/{id}', [App\Http\Controllers\Mobile\MobilePayrollController::class, 'slip'])->name('slip');
        
    });

    // === Bonus Routes Mobile ===
    Route::prefix('bonus')->name('bonus.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Mobile\MobileBonusController::class, 'index'])->name('index');
        Route::get('/detail/{periode}', [\App\Http\Controllers\Mobile\MobileBonusController::class, 'detail'])->name('detail');
        Route::get('/slip/{periode}', [\App\Http\Controllers\Mobile\MobileBonusController::class, 'slip'])->name('slip');
    });

});

// Toko routes
Route::prefix('toko')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->group(function () {
    // Halaman
    Route::get('/pembelian', [TokoController::class, 'pembelian'])->name('toko.pembelian');
    Route::get('/penjualan', [TokoController::class, 'penjualan'])->name('toko.penjualan');
    Route::get('/stock', [TokoController::class, 'stock'])->name('toko.stock');
    Route::post('/barang/create', [TokoController::class, 'createBarang'])->name('toko.barang.create');

    // DataTables
    Route::get('/pembelian/data', [TokoController::class, 'getDataPembelian'])->name('toko.pembelian.data');
    Route::get('/penjualan/data', [TokoController::class, 'getDataPenjualan'])->name('toko.penjualan.data');
    Route::get('/stock/data', [TokoController::class, 'getDataStock'])->name('toko.stock.data');

    // CRUD operations
    Route::post('/pembelian/store', [TokoController::class, 'storePembelian'])->name('toko.pembelian.store');
    Route::post('/penjualan/store', [TokoController::class, 'storePenjualan'])->name('toko.penjualan.store');
    Route::post('/adjust-stock', [TokoController::class, 'adjustStock'])->name('toko.adjust-stock');
    Route::get('/barang/search', [TokoController::class, 'getBarang'])->name('toko.barang.search');
    Route::get('/barang/{id}', [TokoController::class, 'getDetailBarang'])->name('toko.barang.detail');
    Route::put('/barang/{id}', [TokoController::class, 'updateBarang'])->name('toko.barang.update');
    Route::get('/stock/history/{barangId}', [TokoController::class, 'getStockHistory'])->name('toko.stock.history');

    // Common
    Route::get('/{id}', [TokoController::class, 'show'])->name('toko.show');
    Route::get('/{id}/edit', [TokoController::class, 'edit'])->name('toko.edit');
    Route::delete('/{id}', [TokoController::class, 'destroy'])->name('toko.destroy');
    Route::get('{id}/logs', [ProjectController::class,'getUpdateLogs'])->name('toko.logs');

});

Route::prefix('pencairan-bank')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->name('pencairan-bank.')->group(function () {
    Route::get('/', [PencairanBankController::class, 'index'])->name('index');
    Route::get('/detail/{penjualanId}', [PencairanBankController::class, 'detail'])->name('detail');
    Route::get('/create/{penjualanId}', [PencairanBankController::class, 'createByPenjualan'])->name('create-by-penjualan');
    Route::post('/', [PencairanBankController::class, 'store'])->name('store');
    
    // Untuk manage pencairan individu
    Route::get('/pencairan/{id}', [PencairanBankController::class, 'show'])->name('show');
    Route::get('/pencairan/{id}/edit', [PencairanBankController::class, 'edit'])->name('edit');
    Route::put('/pencairan/{id}', [PencairanBankController::class, 'update'])->name('update');
    Route::delete('/pencairan/{id}', [PencairanBankController::class, 'destroy'])->name('destroy');
    
    Route::post('/pencairan/{id}/approve', [PencairanBankController::class, 'approve'])->name('approve');
    Route::post('/pencairan/{id}/reject', [PencairanBankController::class, 'reject'])->name('reject');
    Route::post('/pencairan/{id}/realisasi', [PencairanBankController::class, 'realisasi'])->name('realisasi');

    Route::get('/{id}/edit', [PencairanBankController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PencairanBankController::class, 'update'])->name('update');
    
    // Export routes
    Route::get('/export-excel/{penjualanId}', [PencairanBankController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export-pdf/{penjualanId}', [PencairanBankController::class, 'exportPDF'])->name('export.pdf');
});

// web.php
Route::prefix('penjualan-payment')->middleware(['role:superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing', 'global.app'])->name('penjualan-payment.')->group(function () {
    Route::get('/', [PenjualanPaymentController::class, 'index'])->name('index');
    Route::get('/detail/{penjualanId}', [PenjualanPaymentController::class, 'detail'])->name('detail');
    Route::get('/create/{penjualanId}', [PenjualanPaymentController::class, 'createByPenjualan'])->name('create-by-penjualan');
    Route::post('/', [PenjualanPaymentController::class, 'store'])->name('store');
    
    Route::get('/{id}/edit', [PenjualanPaymentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PenjualanPaymentController::class, 'update'])->name('update');
    Route::delete('/{id}', [PenjualanPaymentController::class, 'destroy'])->name('destroy');
    
    // Export routes
    Route::get('/export-excel/{penjualanId}', [PenjualanPaymentController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export-pdf/{penjualanId}', [PenjualanPaymentController::class, 'exportPDF'])->name('export.pdf');
});

Route::middleware(['auth'])->group(function () {
    Route::get('slip/{payroll_id}', [PayrollController::class, 'downloadSlip'])->name('slip');
});

require __DIR__ . '/auth.php';
