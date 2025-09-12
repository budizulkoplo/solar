<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\CompanyController;

//LAPORAN
use App\Http\Controllers\LaporanController;

// Bagian Mobile UI
use App\Http\Controllers\Mobile\DashboardController;
use App\Http\Controllers\Mobile\MobileTransaksiArmadaController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\MobilePinjamanController;
use App\Http\Controllers\Mobile\MobileStokOpnameController;
use App\Http\Controllers\MobileController;

Route::get('/login', [AuthenticatedSessionController::class, 'create']);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->middleware('global.app:admin')->name('dashboard');
    Route::get('/admin/pesanan-hari-ini', [AdminDashboardController::class, 'pesananHariIni']);
    Route::get('/admin/data-pesanan-hari-ini', [AdminDashboardController::class, 'pesananHariIniData'])->name('dashboard.pesananHariIniData');

});

// UI untuk mobile end users
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    // Form pilih project
    Route::get('/project/select', [MobileProjectController::class, 'select'])->name('project.select');
Route::post('/project/set', [MobileProjectController::class, 'set'])->name('project.set');


    // Dashboard
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
});


Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware('auth', 'global.app')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile', [ProfileController::class, 'upload'])->name('profile.upload');

});

Route::prefix('users')->middleware(['auth', 'verified', 'role:superadmin|admin', 'global.app'])->namespace('Users')->group(function () {
    Route::get('/list', [UsersController::class, 'index'])->name('users.list');
    Route::get('/permission', [UserRoleController::class, 'PermissionByRole']);
    Route::post('/add', [UserRoleController::class, 'addRole']);
    Route::delete('/delr', [UserRoleController::class, 'deleteRole']);
    Route::delete('/delp', [UserRoleController::class, 'deletePermission']);
    Route::get('/getdata', [UsersController::class, 'getdata'])->name('users.getdata');
    Route::get('/assignRole', [UsersController::class, 'kasihRole'])->name('users.assignRole');
    Route::post('/password/update', [UsersController::class, 'updatePassword'])->name('users.updatepassword');
    Route::get('/getcode', [UsersController::class, 'getcode'])->name('users.getcode');
    Route::post('/store', [UsersController::class, 'Store'])->name('users.store');
    Route::delete('/{id}', [UsersController::class, 'destroy'])->name('users.destroy');

});

Route::prefix('companies')
    ->middleware(['auth', 'verified', 'role:superadmin|admin', 'global.app'])
    ->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/store', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::post('/projects/store', [CompanyController::class, 'storeProject'])->name('companies.projects.store');
        Route::delete('/projects/{id}', [CompanyController::class, 'destroyProject'])->name('companies.projects.destroy');
        Route::get('/{id}/edit', [CompanyController::class, 'edit']);
        Route::get('/projects/{id}/edit', [CompanyController::class, 'editProject']);
    });

Route::prefix('unit')->middleware(['auth', 'verified', 'role:superadmin|admin', 'global.app'])->group(function () {
    Route::get('/', [UnitController::class, 'index'])->name('unit.list');
    Route::get('/add', [UnitController::class, 'AddForm'])->name('unit.add');
    Route::get('/edit/{id}', [UnitController::class, 'EditForm'])->name('unit.edit');
    Route::post('/store', [UnitController::class, 'Store'])->name('unit.StorePost');
    Route::put('/store/{id}', [UnitController::class, 'Store'])->name('unit.StorePut');
    Route::get('/hapus/{id}', [UnitController::class, 'Hapus'])->name('unit.Hapus');
});

Route::prefix('setting')
    ->middleware(['auth', 'verified', 'role:superadmin|admin', 'global.app'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingController::class, 'index'])->name('setting.index');
        Route::post('/', [\App\Http\Controllers\SettingController::class, 'update'])->name('setting.update');
    });

Route::get('/ss', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'global.app']);

Route::prefix('roles')->middleware(['auth', 'verified', 'role:superadmin', 'global.app'])->group(function () {
    Route::get('/list', [UserRoleController::class, 'index'])->name('roles.list');
    Route::get('/permission', [UserRoleController::class, 'PermissionByRole']);
    Route::post('/add', [UserRoleController::class, 'addRole']);
    Route::delete('/delr', [UserRoleController::class, 'deleteRole']);
    Route::delete('/delp', [UserRoleController::class, 'deletePermission']);
    Route::post('/swcp', [UserRoleController::class, 'PermissionfromRole'])->name('roles.switch');
});
Route::prefix('menu')->middleware(['auth', 'verified', 'role:superadmin', 'global.app'])->namespace('menus')->group(function () {
    Route::get('/list', [MenuController::class, 'index'])->name('menu.list');
    Route::get('/data/{role}', [MenuController::class, 'datamenu'])->name('menu.data');
    Route::put('/update', [MenuController::class, 'update'])->name('menu.update');
    Route::get('/test', function () {
        return response()->json(request()->menu);
    });
});
Route::prefix('doc')->middleware(['auth', 'verified'])->group(function () {
    Route::get('download/{filename}', function ($filename) {
        if (!Auth::check()) {abort(403);}
        $path = storage_path("app/private/doc/{$filename}");
        if (!file_exists($path)) {abort(404);}
        return Response::download($path);
    });
    Route::get('file/{path}/{filename}', function ($path,$filename) {
        if (!Auth::check()) {abort(403);}
        $path = storage_path("app/private/img/{$path}/{$filename}");
        if (!File::exists($path)) {abort(404);}
        $file = File::get($path);
        $type = File::mimeType($path);
        return Response::make($file, 200)->header("Content-Type", $type);
    });
});


//LAPORAN
Route::prefix('laporan')->middleware(['auth', 'verified', 'role:superadmin|admin', 'global.app'])->group(function () {
    Route::get('/transaksi-armada', [LaporanController::class, 'transaksiArmada'])->name('laporan.transaksi_armada');
    Route::get('/transaksi-armada/data', [LaporanController::class, 'transaksiArmadaData'])->name('laporan.transaksi_armada.data');
    Route::get('/project', [LaporanController::class, 'laporanProject'])->name('laporan.project');
    Route::get('/vendor', [LaporanController::class, 'laporanVendor'])->name('laporan.vendor'); // 🔥 route baru
});

Route::middleware(['auth'])->prefix('mobile/transaksi_armada')->name('mobile.transaksi_armada.')->group(function () {
    Route::get('/create', [MobileTransaksiArmadaController::class, 'create'])->name('create');
    Route::post('/store', [MobileTransaksiArmadaController::class, 'store'])->name('store');
    Route::get('/search', [MobileTransaksiArmadaController::class, 'searchArmada'])->name('search');
    
    Route::get('/{id}/print', [MobileTransaksiArmadaController::class, 'show'])->name('print');
    Route::get('/history', [MobileTransaksiArmadaController::class, 'history'])->name('history');
    Route::get('/{id}/edit', [MobileTransaksiArmadaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MobileTransaksiArmadaController::class, 'update'])->name('update');

});
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/profile', [MobileProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [MobileProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
