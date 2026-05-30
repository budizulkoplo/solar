# Solar ERP - Database Design

Dokumen ini merangkum struktur database aktif project `solar` dan rancangan relasi bisnisnya. Schema ini dibaca dari database lokal `solar` pada 2026-05-30.

## Prinsip Umum

- `notas` adalah header transaksi paling umum untuk project, PT/company, toko, konstruksi, dan beberapa modul lain.
- `nota_transactions` adalah detail baris transaksi nota.
- `nota_payments` adalah pembayaran nota.
- `cashflows` adalah audit mutasi kas/bank/rekening.
- `rekening.saldo` adalah saldo realtime rekening.
- `stock_project` adalah saldo stok realtime per barang/project.
- `stock_history` adalah audit perubahan stok.
- `menus` dan Spatie Permission mengatur akses UI.

## Kelompok Tabel

### Auth, Role, Session

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `role_has_permissions`
- `sessions`
- `password_reset_tokens`
- `cache`
- `cache_locks`
- `jobs`
- `migrations`

### Master Organisasi

- `company_units`: data PT/company.
- `projects`: project/perumahan/area bisnis.
- `retails`: retail/toko.
- `unitkerja`: unit kerja/area HRIS.
- `unitkersja`: tabel legacy mirip `unitkerja`.
- `user_projects`: mapping user ke project.
- `setting`: profil perusahaan/aplikasi.

### Master Keuangan

- `rekening`: rekening kas/bank dan saldo.
- `coa`: chart of accounts.
- `kodetransaksi`: kode transaksi dan mapping COA/laporan.
- `neraca_hdr`: struktur neraca.
- `labarugi_hdr`: struktur laba rugi.
- Tabel backup: `kodetransaksi20251205`, `neraca_hdr_copy1`, `labarugi_hdr_copy1`.

### Transaksi Keuangan Umum

- `notas`
- `nota_transactions`
- `nota_payments`
- `cashflows`
- `angsuran`
- `trans_update_log`
- `transaksi_pindah_buku`
- `transaksi_pindah_buku_logs`
- `transaksi_hdr`, `transaksi_hdr_copy1`

### Toko dan Inventory

- `barang`
- `customer_toko`
- `stock_project`
- `stock_history`
- `notas`
- `nota_transactions`

### Properti/Marketing

- `units`
- `unit_details`
- `unit_details_update_log` bila tersedia di model, tabel aktif bernama `trans_update_log` untuk sebagian log.
- `jenisunit`
- `customers`
- `bookings`
- `penjualans`
- `penjualan_payments`
- `pencairan_bank`

### HRIS

- `pegawai_dtl`
- `kelompokjam`
- `jadwal`
- `presensi`
- `presensi_visit`
- `pengajuan_izin`
- `tugasluar`
- `mastergaji`
- `payroll`
- `bonus`
- `agenda`

### Pembiayaan

- `pembiayaan`
- `pembiayaan_dokumen`
- `pembiayaan_logs`
- `pembiayaan_setoran`

### Aset

- `assets`
- `asset_depreciations`
- `asset_mutations`

### Konstruksi

- `pekerjaan_kontruksi`
- `pekerjaan_progress_logs`
- `vendors`
- `notas`
- `nota_transactions`

### Menu

- `menus`
- `menus20251124`
- `mobilemenu`

## Tabel Inti dan Kolom Penting

### `users`

Primary key: `id`. Ada juga `nip` yang ikut menjadi key legacy.

Kolom penting:

- `nik`, `nip`, `username`, `name`, `password`
- `foto`, `jabatan`, `tanggal_masuk`, `status`
- `email`, `nohp`, `alamat`
- `id_unitkerja`, `ui`
- timestamps, `deleted_at`

Relasi:

- Role via Spatie `model_has_roles`.
- HRIS mengaitkan user/pegawai lewat `nik`/`nip` tergantung fitur.

### `company_units`

Primary key: `id`.

Kolom:

- `company_name`, `siup`, `npwp`, `alamat`
- `logo`, `lokasi`, `lokasi_lock`
- timestamps, `deleted_at`

Dipakai sebagai konteks PT/company dalam transaksi.

### `projects`

Primary key: `id`.

Kolom:

- `idcompany`, `idretail`
- `namaproject`, `lokasi`, `luas`, `logo`
- timestamps, `deleted_at`

Dipakai oleh transaksi project, toko, stock, unit properti.

### `rekening`

Primary key: `idrek`.

Kolom:

- `norek`
- `namarek`
- `saldoawal`
- `saldo`
- `idproject`
- `idcompany`

Aturan:

- Setiap perubahan saldo harus punya catatan di `cashflows`.
- Untuk transfer antar rekening, gunakan `transaksi_pindah_buku` dan dua baris `cashflows`.

### `notas`

Primary key: `id`.

Kolom utama:

- `nota_no`
- `namatransaksi`
- `idproject`
- `idcompany`
- `idretail`
- `idrek`
- `vendor_id`
- `tanggal`
- `cashflow`: `in` atau `out`
- `paymen_method`: `cash` atau `tempo`
- `tgl_tempo`
- `subtotal`, `diskon`, `ppn`, `total`
- `status`: `open`, `paid`, `partial`, `cancel`
- `bukti_nota`
- `nip`, `namauser`
- `type`
- `unit_detail_id`
- `pekerjaan_konstruksi_id`
- `jenis_penjualan`
- `project_tujuan_id`
- `customer_toko_id`
- `keterangan_customer`
- timestamps, `deleted_at`

Relasi:

- `notas.idproject -> projects.id`
- `notas.idcompany -> company_units.id`
- `notas.idretail -> retails.id`
- `notas.idrek -> rekening.idrek`
- `notas.vendor_id -> vendors.id`
- `notas.customer_toko_id -> customer_toko.id`
- `notas.pekerjaan_konstruksi_id -> pekerjaan_kontruksi.id`
- `notas.unit_detail_id -> unit_details.id`

Catatan:

- Nama kolom legacy `paymen_method` harus dipakai apa adanya.
- Tidak semua relasi punya foreign key DB eksplisit.

### `nota_transactions`

Primary key: `id`.

Kolom:

- `idnota`
- `idkodetransaksi`
- `idbarang`
- `description`
- `nominal`
- `jml`
- `total`
- timestamps, `deleted_at`

Relasi:

- `idnota -> notas.id`
- `idkodetransaksi -> kodetransaksi.id`
- `idbarang -> barang.idbarang`

Dipakai untuk detail transaksi project/PT/toko/konstruksi.

### `nota_payments`

Primary key: `id`.

Kolom:

- `idnota`
- `idrek`
- `tanggal`
- `jumlah`
- timestamps

Relasi:

- `idnota -> notas.id`
- `idrek -> rekening.idrek`

### `cashflows`

Primary key: `id`.

Kolom:

- `idrek`
- `kode_transaksi`
- `idnota`
- `tanggal`
- `cashflow`: `in` atau `out`
- `nominal`
- `saldo_awal`
- `saldo_akhir`
- `keterangan`
- timestamps

Aturan:

- Jangan membuat cashflow tanpa update saldo rekening yang sesuai.
- `saldo_awal` dan `saldo_akhir` harus merepresentasikan saldo rekening saat mutasi.

### `barang`

Primary key: `idbarang`.

Kolom:

- `nama_barang`
- `harga_beli`
- `harga_jual`
- `deskripsi`
- timestamps, `deleted_at`

Dipakai toko/inventory.

### `stock_project`

Primary key saat ini: `barang_id`.

Kolom:

- `barang_id`
- `project_id`
- `stock`
- timestamps, `deleted_at`

Catatan desain:

- Secara bisnis saldo stok seharusnya unik per kombinasi `barang_id + project_id`.
- Karena primary key saat ini hanya `barang_id`, hati-hati bila satu barang perlu punya stok di banyak project.

### `stock_history`

Primary key: `id`.

Kolom:

- `barang_id`
- `project_id`
- `tipe`: `masuk`, `keluar`, `adjust`
- `qty`
- `qty_sebelum`
- `qty_sesudah`
- `keterangan`
- `idnota`
- `created_by`
- timestamps

Aturan:

- Setiap perubahan `stock_project.stock` harus mencatat `stock_history`.

### `customer_toko`

Primary key: `id`.

Kolom:

- `kode_customer`
- `nama_lengkap`
- `no_hp`
- `alamat`
- `keterangan`
- timestamps, `deleted_at`

### `customers`

Primary key: `id`.

Kolom penting:

- `kode_customer`, `nama_lengkap`
- data lahir dan gender
- `nik`
- alamat KTP
- `no_hp`, `email`
- `pekerjaan`, `penghasilan_bulanan`
- `nama_ibu_kandung`, `status_pernikahan`
- timestamps, `deleted_at`

Dipakai untuk booking/penjualan unit properti.

### `units`

Primary key: `id`.

Kolom:

- `idproject`
- `tipe`
- `namaunit`
- `idjenis`
- `blok`
- `luastanah`
- `luasbangunan`
- `hargadasar`
- `jumlah`
- timestamps, `deleted_at`

### `unit_details`

Primary key: `id`.

Kolom:

- `idunit`
- `no_rumah`
- `status`
- `shgd`
- `tipe_penjualan`
- `customer_id`
- `booking_id`
- `penjualan_id`
- timestamps, `deleted_at`

Status:

```text
tersedia, booking_unit, bi_check, pemberkasan_bank, acc, tidak_acc,
akad, pencairan, bast, terjual, pemberkasan_notaris
```

### `bookings`

Primary key: `id`.

Kolom:

- `kode_booking`
- `unit_detail_id`
- `customer_id`
- `tanggal_booking`
- `dp_awal`
- `metode_pembayaran_dp`
- `tanggal_jatuh_tempo`
- `keterangan`
- `status_booking`: `active`, `canceled`, `expired`, `completed`
- `created_by`
- timestamps, `deleted_at`

### `penjualans`

Primary key: `id`.

Kolom:

- `kode_penjualan`
- `unit_detail_id`
- `customer_id`
- `booking_id`
- `harga_jual`
- `dp_awal`
- `sisa_pembayaran`
- `metode_pembayaran`
- `tanggal_akad`
- `bank_kredit`
- `tenor_kredit`
- `cicilan_bulanan`
- `keterangan`
- `status_penjualan`: `process`, `completed`, `canceled`
- `created_by`
- timestamps, `deleted_at`

Relasi:

- `penjualans.unit_detail_id -> unit_details.id`
- `penjualans.customer_id -> customers.id`
- `penjualans.booking_id -> bookings.id`

### `penjualan_payments`

Primary key: `id`.

Kolom:

- `kode_payment`
- `penjualan_id`
- `jenis_payment`: `dp_awal`, `termin_1`, `termin_2`, `termin_3`, `lunas`, `lainnya`
- `termin_ke`
- `tanggal_payment`
- `nominal`
- `metode_pembayaran`: `cash`, `transfer`
- bank/rekening info
- `status_payment`: `pending`, `realized`
- `keterangan`, `bukti_payment`
- `created_by`
- timestamps, `deleted_at`

### `pencairan_bank`

Primary key: `id`.

Kolom:

- `kode_pencairan`
- `penjualan_id`
- `bank_kredit`
- `tanggal_pencairan`
- `nominal_pencairan`
- `jenis_pencairan`
- `termin_ke`
- `status_pencairan`: `pending`, `approved`, `realized`, `rejected`
- `keterangan`, `bukti_pencairan`
- `tanggal_realisasi`
- bank account fields
- `created_by`
- timestamps, `deleted_at`

### `pegawai_dtl`

Primary key: `id`.

Kolom penting:

- `nik` unique
- `nama`
- kontrak awal/akhir
- nomor BPJS/JKN/KPJ
- data lahir, alamat, gender, status keluarga
- kontak
- timestamps

### `unitkerja`

Primary key: `id`.

Kolom:

- `namaunit`
- `lokasi`
- `umk`
- `lokasi_lock`
- timestamps

### `kelompokjam`

Primary key: `id`.

Kolom:

- `shift`
- `jammasuk`
- `jampulang`

### `jadwal`

Primary key: `idjadwal`.

Kolom:

- `tgl`
- `pegawai_nik`
- `shift`

### `presensi`

Primary key: `id`.

Kolom:

- `nik`
- `tgl_presensi`
- `jam_in`
- `inoutmode`: `1`, `2`, `3`, `4`
- `foto_in`
- `lokasi`
- timestamps

### `presensi_visit`

Mirip `presensi`, ditambah `keterangan`.

### `pengajuan_izin`

Primary key: `id`.

Kolom:

- `nik`
- `tgl_izin`
- `status`
- `izin_mulai`
- `izin_selesai`
- `lampiran`
- `keterangan`
- `status_approved`
- timestamps
- `deteleted_at` typo legacy

### `mastergaji`

Primary key: `idgaji`.

Kolom:

- `tgl_aktif`
- `nik`
- komponen gaji: `gajipokok`, `masakerja`, `komunikasi`, `transportasi`, `konsumsi`, `tunj_asuransi`, `jabatan`, `asuransi`
- `verifikasi`
- timestamps, `deleted_at`

### `payroll`

Primary key: `id`.

Kolom:

- `periode`
- `nik`
- `nama`
- rekap waktu: `jmlabsen`, `lembur`, `terlambat`, `cuti`
- komponen gaji dan potongan: `gajipokok`, `pek_tambahan`, `masakerja`, `komunikasi`, `transportasi`, `konsumsi`, `tunj_asuransi`, `jabatan`, `cicilan`, `asuransi`, `zakat`
- timestamps, `deleted_at`

### `bonus`

Primary key: `id`.

Kolom:

- `periode`
- `nik`
- `nama`
- `nominal`
- `keterangan`
- timestamps, `deleted_at`

### `pembiayaan`

Primary key: `id`.

Kolom:

- `kode_pembiayaan`
- `judul`
- `jenis`: `company`, `project`
- `idcompany`
- `idproject`
- `rekening_id`
- `nominal`
- `tanggal`
- `deskripsi`
- `metode_pembayaran`: `cash`, `transfer`
- `status`: `draft`, `approved`, `completed`, `rejected`
- `created_by`
- timestamps, `deleted_at`

Catatan:

- Model `Pembiayaan` punya accessor/mutator nominal untuk normalisasi format Indonesia.
- Model juga mengenal status `active`, `overdue`, `lunas`; cek controller/schema sebelum menambah status baru.

### `pembiayaan_setoran`

Primary key: `id`.

Kolom:

- `pembiayaan_id`
- `kode_setoran`
- `tanggal`
- `pokok`
- `administrasi`
- `margin`
- `total`
- `deskripsi`
- `bukti_path`
- `status`: `pending`, `paid`, `canceled`
- `created_by`
- timestamps, `deleted_at`

### `pembiayaan_dokumen`

Kolom:

- `pembiayaan_id`
- `nama_file`
- `path_file`
- `tipe_file`
- `size_file`
- `created_by`
- timestamps, `deleted_at`

### `pembiayaan_logs`

Kolom:

- `pembiayaan_id`
- `log_type`
- `description`
- `created_by`
- timestamps, `deleted_at`

### `assets`

Primary key: `id`.

Kolom:

- `kode_aset`
- `nama_aset`
- `idkodetransaksi`, `kodetransaksi`
- `tanggal_pembelian`
- `tanggal_mulai_susut`
- `harga_perolehan`
- `nilai_residu`
- `umur_ekonomis`
- `metode_penyusutan`
- `persentase_susut`
- `status`: `aktif`, `nonaktif`, `terjual`, `hilang`, `rusak`
- `lokasi`, `pic`, `keterangan`
- `idcompany`, `idproject`, `idretail`, `idnota`
- timestamps, `deleted_at`

### `asset_depreciations`

Kolom:

- `asset_id`
- `periode`
- `bulan_ke`
- `nilai_penyusutan`
- `akumulasi_penyusutan`
- `nilai_buku`
- `status`: `terbentuk`, `terposting`, `batal`
- `idnota`
- `keterangan`

### `asset_mutations`

Kolom:

- `asset_id`
- `idnota`
- `jenis`
- `subjenis`
- `nilai`
- `pihak_terkait`
- `tanggal_mulai`, `tanggal_selesai`, `tanggal`
- `keterangan`

### `pekerjaan_kontruksi`

Primary key: `id`.

Kolom:

- `idproject`
- `idkodetransaksi`
- `nama_pekerjaan`
- `jenis_pekerjaan`
- `lokasi`
- `volume`
- `satuan`
- `anggaran`
- `realisasi_anggaran`
- `tanggal_mulai`
- `tanggal_selesai`
- `jumlah`
- `harga_satuan`
- `status`: `planning`, `ongoing`, `completed`, `canceled`
- `keterangan`
- timestamps, `deleted_at`

### `pekerjaan_progress_logs`

Kolom:

- `pekerjaan_id`
- `progress`
- `status`
- `realisasi_anggaran`
- `keterangan`
- `tanggal_update`
- `created_by`
- timestamps

### `vendors`

Primary key: `id`.

Kolom:

- `namavendor`
- `jenis`: `pekerjaan`, `material`
- `npwp`
- `rekening`
- `telp`
- `alamat`
- timestamps, `deleted_at`

### `menus`

Primary key: `id`.

Kolom:

- `name`
- `link`
- `parent_id`
- `role`
- `seq`
- `icon`
- `module`
- timestamps, `deleted_at`

Aturan:

- `GlobalApp` membaca `menus` untuk membangun sidebar dan akses route.
- `link` sering menjadi base route, bukan URL penuh.

## Relasi Bisnis Utama

### Nota Keuangan

```text
notas 1--n nota_transactions
notas 1--n nota_payments
notas 1--n cashflows
notas n--1 rekening
notas n--1 projects
notas n--1 company_units
notas n--1 vendors
```

### Toko

```text
barang 1--n nota_transactions
barang 1--n stock_history
barang 1--1/m stock_project
projects 1--n stock_project
projects 1--n stock_history
customer_toko 1--n notas
```

### Properti

```text
projects 1--n units
units 1--n unit_details
customers 1--n bookings
customers 1--n penjualans
unit_details 1--0/1 bookings
unit_details 1--0/1 penjualans
penjualans 1--n penjualan_payments
penjualans 1--n pencairan_bank
```

### HRIS

```text
pegawai_dtl.nik 1--n presensi.nik
pegawai_dtl.nik 1--n presensi_visit.nik
pegawai_dtl.nik 1--n pengajuan_izin.nik
pegawai_dtl.nik 1--n jadwal.pegawai_nik
pegawai_dtl.nik 1--n payroll.nik
pegawai_dtl.nik 1--n bonus.nik
```

### Pembiayaan

```text
pembiayaan 1--n pembiayaan_dokumen
pembiayaan 1--n pembiayaan_logs
pembiayaan 1--n pembiayaan_setoran
pembiayaan n--1 rekening
pembiayaan n--1 company_units
pembiayaan n--0/1 projects
```

### Aset

```text
assets 1--n asset_depreciations
assets 1--n asset_mutations
assets n--0/1 notas
assets n--0/1 projects
assets n--0/1 company_units
```

## Rekomendasi Normalisasi Berikutnya

Prioritas perbaikan schema bila project akan dikembangkan lebih jauh:

1. Tambahkan unique key `stock_project(barang_id, project_id)` dan ubah primary key agar stok multi-project aman.
2. Konsistenkan primary key model Laravel (`id` atau deklarasi `$primaryKey` di semua model legacy).
3. Tambahkan foreign key untuk tabel inti yang paling sering dipakai: `notas`, `nota_transactions`, `cashflows`, `stock_history`, `penjualans`.
4. Standarkan nama typo legacy hanya bila siap migrasi besar: `paymen_method`, `pekerjaan_kontruksi`, `deteleted_at`, `unitkersja`.
5. Pisahkan backup tables dari database produksi atau tandai dengan dokumentasi khusus.
6. Tambahkan audit table yang konsisten untuk perubahan status unit, nota, pembiayaan, dan stok.

## Checklist Saat Menambah Tabel Baru

- Buat migration baru, jangan edit migration lama yang sudah pernah jalan.
- Tambahkan model dengan `$table`, `$primaryKey`, `$fillable`, `$casts`, dan relation.
- Jika tabel transaksi memengaruhi saldo, buat juga tabel audit/history.
- Jika perlu tampil di menu, insert/update `menus` dan role akses.
- Jika butuh DataTables, ikuti pola `getdata` di controller existing.
- Update dokumen ini setelah schema berubah.
