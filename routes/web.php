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

// Mobile
use App\Http\Controllers\Mobile\DashboardController;
use App\Http\Controllers\Mobile\MobileProjectController;
use App\Http\Controllers\Mobile\MobileTransaksiArmadaController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\MobilePinjamanController;
use App\Http\Controllers\Mobile\MobileStokOpnameController;

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
    Route::prefix('profile')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/', [ProfileController::class, 'upload'])->name('profile.upload');
    });

    // Users
    Route::prefix('users')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
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

    // Companies
    Route::prefix('companies')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/store', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::post('/projects/store', [CompanyController::class, 'storeProject'])->name('companies.projects.store');
        Route::delete('/projects/{id}', [CompanyController::class, 'destroyProject'])->name('companies.projects.destroy');
        Route::get('/{id}/edit', [CompanyController::class, 'edit']);
        Route::get('/projects/{id}/edit', [CompanyController::class, 'editProject']);
    });

    // Rekening
    Route::prefix('rekening')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/', [RekeningController::class, 'index'])->name('rekening.index');
        Route::post('/store', [RekeningController::class, 'store'])->name('rekening.store');
        Route::get('/{id}/edit', [RekeningController::class, 'edit'])->name('rekening.edit');
        Route::delete('/{id}', [RekeningController::class, 'destroy'])->name('rekening.destroy');
    });

    // Vendors
    Route::prefix('vendors')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/getdata', [VendorController::class, 'getData'])->name('vendors.getdata');
        Route::post('/store', [VendorController::class, 'store'])->name('vendors.store');
        Route::delete('/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        Route::post('{id}/update-jenis', [VendorController::class, 'updateJenis'])->name('vendors.updateJenis');
    });

    // User Projects
    Route::prefix('user-projects')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/', [UserProjectController::class, 'index'])->name('user-projects.index');
        Route::post('/toggle', [UserProjectController::class, 'toggle'])->name('user-projects.toggle');
        Route::get('/{userId}', [UserProjectController::class, 'getUserProjects']);
    });

    // Units
    Route::prefix('units')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('units.index');
        Route::get('/getdata', [UnitController::class, 'getData'])->name('units.getdata');
        Route::post('/store', [UnitController::class, 'store'])->name('units.store');
        Route::get('/{id}/edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::delete('/{id}', [UnitController::class, 'destroy'])->name('units.destroy');
        Route::get('/details', [UnitDetailController::class, 'index'])->name('units.details.index');
    });

    // Setting
    Route::prefix('setting')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
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

    // Laporan
    Route::prefix('laporan')->middleware(['role:superadmin|admin', 'global.app'])->group(function () {
        Route::get('/transaksi-armada', [LaporanController::class, 'transaksiArmada'])->name('laporan.transaksi_armada');
        Route::get('/transaksi-armada/data', [LaporanController::class, 'transaksiArmadaData'])->name('laporan.transaksi_armada.data');
        Route::get('/project', [LaporanController::class, 'laporanProject'])->name('laporan.project');
        Route::get('/vendor', [LaporanController::class, 'laporanVendor'])->name('laporan.vendor');
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

// UI untuk mobile (auth tapi project optional → bisa pisah middleware sendiri)
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/project/select', [MobileProjectController::class, 'select'])->name('project.select');
    Route::post('/project/set', [MobileProjectController::class, 'set'])->name('project.set');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/profile', [MobileProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [MobileProfileController::class, 'update'])->name('profile.update');
});

// Transaksi Armada (Mobile)
Route::middleware(['auth'])->prefix('mobile/transaksi_armada')->name('mobile.transaksi_armada.')->group(function () {
    Route::get('/create', [MobileTransaksiArmadaController::class, 'create'])->name('create');
    Route::post('/store', [MobileTransaksiArmadaController::class, 'store'])->name('store');
    Route::get('/search', [MobileTransaksiArmadaController::class, 'searchArmada'])->name('search');
    Route::get('/{id}/print', [MobileTransaksiArmadaController::class, 'show'])->name('print');
    Route::get('/history', [MobileTransaksiArmadaController::class, 'history'])->name('history');
    Route::get('/{id}/edit', [MobileTransaksiArmadaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MobileTransaksiArmadaController::class, 'update'])->name('update');
});

require __DIR__ . '/auth.php';
