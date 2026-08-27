# Solar ERP - Project Context

Dokumen ini adalah tour guide untuk pengembangan lanjutan project `solar`.

## Identitas Project

Nama aplikasi: Solar System ERP.

Tujuan aplikasi:

- Mengelola company, project, retail/toko, transaksi pemasukan/pengeluaran, rekening, dan cashflow.
- Mengelola penjualan unit properti, booking, customer, pembayaran, dan pencairan bank.
- Mengelola HRIS: presensi, visit, izin, payroll, bonus, jadwal.
- Mengelola toko: pembelian, penjualan, stock per project, dan invoice.
- Mengelola pembiayaan, aset, konstruksi, dan laporan keuangan.

## Stack dan Dependency

Backend:

- Laravel 12.
- PHP 8.2.
- MySQL/MariaDB.
- Spatie Laravel Permission.
- Laravel Breeze auth.
- Laravel Octane tersedia.

Frontend dan utility:

- Blade.
- Bootstrap/AdminLTE/Tabler assets.
- Tailwind/Vite masih ada.
- Yajra DataTables.
- SweetAlert, jQuery, Bootstrap Datepicker.

Export/cetak:

- DomPDF.
- Maatwebsite Excel / PhpSpreadsheet.
- Simple QR Code.
- ESC/POS printer.

## Struktur Folder

```text
app/Http/Controllers
app/Http/Controllers/Auth
app/Http/Controllers/Mobile
app/Http/Middleware
app/Models
app/Helpers
database/migrations
resources/views
resources/views/layouts
resources/views/master
resources/views/transaksi
resources/views/mobile
resources/views/hris
resources/views/laporan
routes/web.php
routes/auth.php
```

## Routing dan Middleware

Route utama ada di `routes/web.php`.

Pola akses:

- `/` redirect ke login.
- `/choose-project` dipakai untuk memilih project/module aktif.
- Mayoritas web route berada di group `auth`, `verified`, `check.project`.
- Middleware `global.app` membangun menu dan melakukan pembatasan akses berdasarkan role/module/menu.
- Route mobile memakai prefix `/mobile` dan umumnya hanya `auth`.

Middleware custom:

- `app/Http/Middleware/CheckActiveProject.php`
  - Redirect user ke mobile home bila belum ada `active_project_id`, kecuali route choose-project, logout, dan mobile.
- `app/Http/Middleware/GlobalApp.php`
  - Ambil menu dari tabel `menus`.
  - Filter menu berdasarkan role dan `active_project_module`.
  - Menentukan route yang boleh diakses berdasarkan link menu.

Role yang sering dipakai:

```text
superadmin, admin, hrd, pengurus, keuangan, direktur, manager, adminpt, marketing
```

## Modul dan Controller

Master data:

- `UsersController`, `UserRoleController`
- `CompanyController`
- `ProjectController`
- `UnitController`, `UnitDetailController`
- `UnitKerjaController`, `PlottingUnitKerjaController`
- `CustomerController`
- `VendorController`
- `RekeningController`
- `CoaController`
- `KodetransaksiController`
- `MenuController`
- `SettingController`

Transaksi company/project:

- `PTController`: transaksi company/PT.
- `ProjectController`: transaksi project.
- `NotaController`: controller nota lama/general.
- `PindahBukuController`: transfer antar rekening.
- `PendingPiutangController`: pending pembayaran/piutang project.
- `CompanyPendingPiutangController`: pending pembayaran/piutang company.

Toko:

- `TokoController`
- Views: `resources/views/transaksi/toko/*`
- Tabel utama: `notas`, `nota_transactions`, `stock_project`, `stock_history`, `customer_toko`, `barang`.

Properti dan marketing:

- `AgencySaleController`
- `PenjualanPaymentController`
- `PencairanBankController`
- Model penting: `Unit`, `UnitDetail`, `Booking`, `Customer`, `Penjualan`, `PenjualanPayment`, `PencairanBank`.

HRIS:

- `AbsensiController`
- `PengajuanIzinController`
- `PayrollController`
- `BonusController`
- `MasterGajiController`
- `KelompokJamController`
- `JadwalController`
- Model penting: `Presensi`, `PresensiVisit`, `Pengajuanizin`, `Payroll`, `Bonus`, `MasterGaji`, `PegawaiDtl`.

Mobile:

- `Mobile\DashboardController`
- `Mobile\PresensiController`
- `Mobile\KalenderController`
- `Mobile\MobilePayrollController`
- `Mobile\MobileBonusController`
- `Mobile\QuranController`

Pembiayaan:

- `PembiayaanController`
- Tabel: `pembiayaan`, `pembiayaan_dokumen`, `pembiayaan_logs`, `pembiayaan_setoran`.

Aset:

- `AssetTransactionController`
- Tabel: `assets`, `asset_depreciations`, `asset_mutations`.

Konstruksi:

- `PekerjaanKonstruksiController`
- `ConstructionTransactionController`
- Tabel: `pekerjaan_kontruksi`, `pekerjaan_progress_logs`, `notas`.

Laporan:

- `LaporanController`
- Views: `resources/views/laporan/*`, `resources/views/transaksi/laporan/*`, `resources/views/hris/laporan/*`.
- Laporan keuangan: `/laporan/neraca`, `/laporan/laba-rugi`, `/laporan/perubahan-ekuitas`, `/laporan/kategori`.
- `/laporan/kategori` menjumlahkan `nota_transactions` berdasarkan kode transaksi di master `/kodetransaksi`, lalu memisahkan detail ke pemasukan (`cashflow = in`) dan pengeluaran (`cashflow = out`).

## Alur Keuangan Umum

Pola transaksi cash/tempo:

1. Buat `notas`.
2. Buat satu atau banyak `nota_transactions`.
3. Jika cash, buat `nota_payments`.
4. Buat `cashflows` dengan `cashflow = in` atau `out`.
5. Update saldo `rekening.saldo`.
6. Jika update/delete, rollback cashflow dan saldo rekening lama dulu.
7. Catat perubahan di `trans_update_log`.

Tabel penting:

- `notas`: header transaksi.
- `nota_transactions`: detail transaksi.
- `nota_payments`: pembayaran nota.
- `cashflows`: audit mutasi rekening.
- `rekening`: saldo rekening realtime.

## Alur Toko

Pembelian toko:

1. Route `toko/pembelian`.
2. Controller `TokoController@storePembelian`.
3. Header masuk `notas` dengan `cashflow = out`.
4. Detail barang masuk `nota_transactions`.
5. Stok `stock_project` bertambah.
6. Riwayat stok masuk `stock_history` tipe `masuk`.
7. Jika cash, rekening/cashflow diproses.

Penjualan toko:

1. Route `toko/penjualan`.
2. Controller `TokoController@storePenjualan`.
3. Header masuk `notas` dengan `cashflow = in`.
4. Detail barang masuk `nota_transactions`.
5. Stok `stock_project` berkurang.
6. Riwayat stok masuk `stock_history` tipe `keluar`.
7. Customer toko bisa dibuat di `customer_toko`.

Stock:

- Saldo realtime: `stock_project`.
- Audit history: `stock_history`.
- Master barang: `barang`.

## Alur Properti

Data inti:

- `projects`: project/perumahan.
- `units`: tipe/unit induk dalam project.
- `unit_details`: unit/blok/nomor rumah individual.
- `customers`: calon pembeli/pembeli.
- `bookings`: booking unit.
- `penjualans`: transaksi penjualan unit.
- `penjualan_payments`: pembayaran dari customer.
- `pencairan_bank`: pencairan bank untuk penjualan kredit/KPR.

Status unit di `unit_details.status`:

```text
tersedia, booking_unit, bi_check, pemberkasan_bank, acc, tidak_acc,
akad, pencairan, bast, terjual, pemberkasan_notaris
```

## Alur HRIS

Data inti:

- `pegawai_dtl`: master pegawai by `nik`.
- `unitkerja`: lokasi/unit kerja.
- `kelompokjam`: shift dan jam kerja.
- `jadwal`: jadwal pegawai.
- `presensi`: presensi reguler.
- `presensi_visit`: visit luar kantor.
- `pengajuan_izin`: izin/sakit/cuti.
- `mastergaji`: komponen gaji.
- `payroll`: hasil payroll per periode.
- `bonus`: bonus per periode.

Mobile presensi:

- Prefix route: `/mobile/presensi`.
- Banyak fitur mobile tidak melewati `check.project`.
- Radius/lokasi memakai `unitkerja.lokasi` dan `lokasi_lock`.

## Alur Pembiayaan

Data inti:

- `pembiayaan`: header pembiayaan company/project.
- `pembiayaan_dokumen`: file pendukung.
- `pembiayaan_logs`: audit status/perubahan.
- `pembiayaan_setoran`: setoran pokok/admin/margin.

Pola:

- Pembiayaan awal biasanya membuat cashflow keluar dari rekening.
- Setoran membuat cashflow masuk.
- Status berubah berdasarkan total setoran.

## Alur Aset

Data inti:

- `assets`: master aset.
- `asset_depreciations`: penyusutan periodik.
- `asset_mutations`: pembelian, penjualan, rental, disposal, mutasi aset.

Relasi konteks:

- Aset bisa dikaitkan ke company, project, retail, nota, dan kode transaksi.

## Alur Konstruksi

Data inti:

- `pekerjaan_kontruksi`: pekerjaan dan anggaran.
- `pekerjaan_progress_logs`: progress pekerjaan.
- `notas`: penerimaan/pengeluaran terkait pekerjaan konstruksi.

Catatan:

- Nama tabel memakai typo legacy `kontruksi`, bukan `konstruksi`.

## Menu dan Permission

Menu disimpan di tabel `menus`:

- `name`: label menu.
- `link`: route/base route.
- `parent_id`: parent menu.
- `role`: daftar role dalam string.
- `seq`: urutan.
- `icon`: icon.
- `module`: module aktif, misalnya HRIS/project/PT/toko.

Permission role memakai tabel Spatie:

- `roles`
- `permissions`
- `model_has_roles`
- `role_has_permissions`

## File Legacy/Backup

Jangan ubah kecuali diminta:

- `*_copy1`
- `menus20251124`
- `kodetransaksi20251205`
- controller/view bertanggal seperti `DashboardController20251202.php`, `TicketApiController20251203.php`
- file `_ori`

## Catatan Risiko

- Banyak table lama tidak punya foreign key eksplisit walaupun relasinya dipakai di model/controller.
- Beberapa kolom/model tidak sinkron penuh dengan migration lama. Untuk keputusan schema, cek database aktif dan model.
- Beberapa primary key bukan `id`, misalnya `idbarang`, `idrek`, `idgaji`, `idjadwal`.
- Uang disimpan decimal besar. Jangan format Rupiah sebelum simpan ke DB.
- Untuk saldo rekening/stok, jangan update saldo tanpa membuat catatan audit terkait.
