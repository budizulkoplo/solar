# Solar ERP - Panduan Codex

Dokumen ini menjadi pintu masuk saat melanjutkan pengembangan project `solar`.
Sebelum mengubah kode, baca file ini lalu lanjutkan ke:

- `docs/PROJECT_CONTEXT.md`
- `docs/DATABASE_DESIGN.md`

## Ringkasan Project

`solar` adalah aplikasi ERP Laravel untuk operasional company/project, toko, transaksi kas, penjualan unit properti, HRIS, payroll, pembiayaan, aset, konstruksi, dan laporan keuangan.

Tech stack utama:

- Laravel 12, PHP 8.2.
- MySQL/MariaDB.
- Spatie Permission untuk role/permission.
- Laravel Breeze auth.
- Yajra DataTables untuk tabel server-side.
- DomPDF, Excel/PhpSpreadsheet, QR Code, ESC/POS printer.
- Bootstrap/AdminLTE/Tabler assets, Tailwind/Vite masih tersedia.

## Cara Kerja Lokal

Lokasi project:

```text
D:\laragon\www\solar
```

Command umum:

```text
composer install
npm install
php artisan migrate
php artisan serve
npm run dev
php artisan test
php artisan route:list
php artisan view:clear
```

Catatan:

- Project memakai `.env` lokal. Jangan commit isi `.env`.
- Ada dependency Redis/Octane di `composer.json`, tetapi cek konfigurasi `.env` sebelum mengaktifkan queue/cache berbasis Redis.
- Saat debugging route/menu, cek session `active_project_id` dan `active_project_module`.

## Struktur Penting

```text
app/Http/Controllers
app/Http/Controllers/Mobile
app/Http/Middleware
app/Models
database/migrations
resources/views
routes/web.php
app/Helpers/rupiah.php
```

Layout utama:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/layouts/mobile.blade.php`
- `resources/views/layouts/bottomNav.blade.php`
- `resources/views/partials/*`

## Pola Akses

Route web utama dilindungi:

- `auth`
- `verified`
- `check.project`
- sering juga `global.app`
- banyak route memakai middleware role: `superadmin|admin|hrd|pengurus|keuangan|direktur|manager|adminpt|marketing`

Middleware penting:

- `CheckActiveProject`: user harus memilih project aktif kecuali route choose-project/logout/mobile.
- `GlobalApp`: membangun menu berdasarkan role dan module aktif, lalu membatasi akses route berdasarkan menu.

Project selection:

- Route: `/choose-project`
- Controller: `ProjectSelectionController`
- Session yang penting: `active_project_id`, `active_project_module`.

## Modul Utama

- Master: company, project, rekening, vendor, unit, customer, COA, kode transaksi, menu, role, user.
- Project/PT transaction: nota pemasukan/pengeluaran berbasis `notas`, `nota_transactions`, `nota_payments`, `cashflows`.
- Toko: pembelian, penjualan, stok project, customer toko.
- Properti: unit, unit detail, booking, penjualan unit, payment, pencairan bank.
- HRIS: presensi, visit, izin, payroll, bonus, jadwal, kelompok jam, master gaji.
- Pembiayaan: pembiayaan company/project, dokumen, setoran, log.
- Aset: asset, depreciation, mutation.
- Konstruksi: pekerjaan konstruksi, progress, transaksi project.
- Mobile: dashboard, presensi, kalender, payroll, bonus, Quran.
- Laporan: dashboard, cashflow, neraca, laba rugi, perubahan ekuitas, transaksi by kategori, penjualan, booking, HRIS reports.

## Pola Data Transaksi

Untuk transaksi keuangan, pola yang sering dipakai:

1. Header disimpan ke `notas`.
2. Detail transaksi disimpan ke `nota_transactions`.
3. Jika pembayaran cash, `nota_payments` dibuat.
4. Mutasi rekening dicatat di `cashflows`.
5. Saldo rekening di `rekening.saldo` ikut berubah.
6. Perubahan transaksi dicatat di `trans_update_log`.

Untuk toko:

1. Pembelian/penjualan tetap memakai `notas`.
2. Item barang memakai `nota_transactions.idbarang`.
3. Saldo stok per project disimpan di `stock_project`.
4. Riwayat stok disimpan di `stock_history`.

## Catatan Pengembangan

- Pertahankan transaksi DB saat menyentuh uang, stok, atau status unit.
- Jangan hanya update header; cek dampak ke detail, cashflow, rekening, stock, dan log.
- Hindari mengubah tabel copy/backup seperti `*_copy1`, `menus20251124`, `kodetransaksi20251205`, kecuali memang diminta.
- Banyak primary key lama tidak mengikuti default Laravel (`idbarang`, `idrek`, `idgaji`, `idjadwal`). Cek model sebelum memakai route-model binding.
- Beberapa nama kolom punya typo/legacy: `paymen_method`, `pekerjaan_kontruksi`, `deteleted_at`, `unitkersja`.
- Untuk fitur baru, pakai migration baru, model relation jelas, dan update dokumentasi di `docs/`.

## Saat Membuat Fitur Baru

Checklist cepat:

- Pahami module dan route yang relevan di `routes/web.php`.
- Cek controller existing untuk pola validasi, transaksi DB, response DataTables, dan view.
- Cek model `$fillable`, `$table`, `$primaryKey`, soft delete, casts.
- Cek menu `menus` jika fitur perlu muncul di sidebar.
- Jika menyentuh transaksi uang: sinkronkan `notas`, `nota_transactions`, `nota_payments`, `cashflows`, `rekening`.
- Jika menyentuh stok: sinkronkan `stock_project` dan `stock_history`.
- Jalankan minimal `php -l` untuk file PHP yang diedit, dan `php artisan view:clear` bila mengubah Blade yang sudah tampil aneh karena cache.
