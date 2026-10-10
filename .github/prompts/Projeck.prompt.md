<div align="center">

# 🗳️ E-Voting OSIS

**Aplikasi pemilihan Ketua dan Wakil Ketua OSIS berbasis web yang sederhana, cepat, dan mudah dikelola.**

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-CDN-06B6D4?logo=tailwindcss&logoColor=white)
![Status](https://img.shields.io/badge/status-proyek%20dasar-orange)

[Fitur](#-fitur) •
[Screenshot](#-screenshot) •
[Instalasi](#-instalasi-lokal) •
[Konfigurasi](#-konfigurasi-database) •
[Keamanan](#-keamanan-dan-catatan-produksi) •
[Struktur Proyek](#-struktur-proyek)

</div>

---

## 📖 Tentang Proyek

**E-Voting OSIS** adalah aplikasi pemungutan suara elektronik untuk pemilihan Ketua dan Wakil Ketua OSIS di lingkungan sekolah. Dibangun dengan **PHP native** dan **MySQL/MariaDB**, aplikasi ini ringan, tidak membutuhkan framework, dan mudah dijalankan di server lokal maupun hosting biasa.

Siswa masuk menggunakan **NIS** (ketik manual atau pindai barcode kartu pelajar), memilih pasangan kandidat, dan suara tersimpan dengan aturan **satu siswa satu suara**. Panitia dapat mengelola seluruh data pemilihan melalui **panel admin**.

## ✨ Fitur

### Untuk Siswa
- 🔑 **Login dengan NIS** secara manual atau melalui **pemindaian barcode** menggunakan kamera.
- 🗳️ **Satu suara per siswa** yang terdaftar.
- 📱 **Antarmuka responsif** untuk ponsel, tablet, dan komputer.

### Untuk Panitia / Admin
- 👥 Pengelolaan **data siswa** (tambah, ubah, hapus).
- 🧑‍💼 Pengelolaan **data kandidat** beserta foto.
- 📊 Pemantauan **hasil pemilihan**.
- 📥 **Impor** dan 📤 **ekspor** data siswa.
- 🎨 Pengaturan **logo pemilihan** dan **teks copyright** dari panel admin.

## 📸 Screenshot

> Simpan tangkapan layar di folder `docs/screenshots/` (buat folder jika belum ada), lalu gunakan format berikut.

| Halaman Login | Halaman Voting | Dashboard Admin |
| :---: | :---: | :---: |
| ![Halaman login](docs/screenshots/login.png) | ![Halaman voting](docs/screenshots/voting.png) | ![Dashboard admin](docs/screenshots/admin-dashboard.png) |

## 🛠️ Teknologi

| Komponen | Teknologi |
| --- | --- |
| Backend | PHP native dengan PDO |
| Database | MySQL atau MariaDB |
| Frontend | HTML, JavaScript, Tailwind CSS (CDN) |
| Pemindai barcode | [html5-qrcode](https://github.com/mebjas/html5-qrcode) |

## 📋 Persyaratan Sistem

- PHP dengan ekstensi **PDO MySQL** aktif
- MySQL atau MariaDB
- Apache atau server PHP lainnya (Nginx + PHP-FPM, `php -S`, dsb.)
- Koneksi internet untuk memuat Tailwind CSS dan pustaka pemindai barcode dari CDN
- Browser modern dengan dukungan kamera (untuk fitur pemindaian barcode)

## 🚀 Instalasi Lokal

1. **Clone repository** ke folder web server. Untuk XAMPP/LAMPP, folder umumnya bernama `htdocs`.

   ```bash
   git clone <URL_REPOSITORY> pemilos
   ```

2. **Jalankan Apache dan MySQL/MariaDB.**

3. **Atur kredensial database** melalui environment variable (lihat [Konfigurasi Database](#-konfigurasi-database)).

4. **Pastikan folder unggahan dapat ditulis** oleh web server.

   ```bash
   chmod -R 775 assets/uploads/
   ```

5. **Buka aplikasi** di browser:

   ```
   http://localhost/pemilos/
   ```

### Inisialisasi Database

Terdapat dua cara menyiapkan database:

| Metode | Keterangan |
| --- | --- |
| **Otomatis** (default) | Saat pertama kali terhubung, `config/database.php` membuat database, tabel, dan data awal. Pengguna database harus memiliki izin `CREATE` database dan tabel. |
| **Manual** | Impor berkas `database.sql` melalui phpMyAdmin atau klien MySQL sebelum membuka aplikasi. |

Contoh impor manual melalui terminal:

```bash
mysql -u root -p < database.sql
```

## ⚙️ Konfigurasi Database

Konfigurasi diberikan melalui **environment variable**. Jika tidak ditentukan, aplikasi memakai nilai default berikut:

| Variabel | Default | Keterangan |
| --- | --- | --- |
| `DB_HOST` | `localhost` | Alamat server database |
| `DB_PORT` | `3306` | Port database |
| `DB_DATABASE` | `evoting_osis` | Nama database |
| `DB_USERNAME` | `root` | Pengguna database |
| `DB_PASSWORD` | *(kosong)* | Kata sandi database |

Contoh pengaturan pada Apache (`.htaccess` atau virtual host):

```apache
SetEnv DB_HOST     localhost
SetEnv DB_PORT     3306
SetEnv DB_DATABASE evoting_osis
SetEnv DB_USERNAME evoting_user
SetEnv DB_PASSWORD kata_sandi_kuat
```

Contoh untuk PHP built-in server:

```bash
DB_USERNAME=evoting_user DB_PASSWORD=kata_sandi_kuat php -S localhost:8000
```

> ⚠️ **Jangan** menyimpan kredensial produksi di repository publik. Gunakan pengguna database khusus dengan hak akses seperlunya.

## 👤 Akun dan Data Awal

- Database membuat **satu akun admin awal** dengan NIS `adminosis`.
- Login aplikasi menggunakan **NIS saja, tanpa kata sandi**.
- Database juga menambahkan **dua kandidat contoh**.

**Sebelum pemilihan sungguhan:**

- [ ] Ganti atau hapus akun admin awal.
- [ ] Ganti atau hapus kandidat contoh melalui panel admin.
- [ ] Impor data siswa yang sebenarnya melalui panel admin.
- [ ] Jangan memasukkan data siswa nyata ke dalam repository.

## 🧭 Alur Penggunaan

1. **Persiapan** — Admin login, mengatur logo dan teks copyright, menambahkan kandidat, dan mengimpor data siswa.
2. **Pemungutan suara** — Siswa membuka halaman login, memasukkan atau memindai NIS, lalu memilih kandidat.
3. **Rekapitulasi** — Panitia memantau dan mengunduh hasil dari panel admin.

## 🔐 Keamanan dan Catatan Produksi

Aplikasi ini ditujukan sebagai **proyek dasar**. Sebelum dipakai pada pemilihan sungguhan, perhatikan hal berikut:

- Login hanya menggunakan NIS tanpa kata sandi. **Tinjau dan perkuat mekanisme autentikasi**, terutama untuk akun admin.
- Gunakan **HTTPS**. Akses kamera browser umumnya hanya berfungsi pada `localhost` atau koneksi HTTPS.
- Batasi hak akses pengguna database dan jangan gunakan akun `root` di produksi.
- Batasi jenis dan ukuran berkas yang dapat diunggah ke `assets/uploads/`.
- Lakukan **backup database** secara berkala, terutama sebelum dan sesudah hari pemilihan.
- Lakukan uji coba penuh (simulasi) sebelum hari pemilihan.

## 📝 Catatan Penggunaan

- Izin tulis diperlukan pada `assets/uploads/` agar admin dapat mengunggah foto kandidat dan logo.
- Pemindaian barcode memerlukan izin kamera; **login manual tetap tersedia** sebagai cadangan.
- Tailwind CSS dan html5-qrcode dimuat dari CDN, sehingga dibutuhkan koneksi internet.

## 🩺 Pemecahan Masalah

| Masalah | Solusi |
| --- | --- |
| Koneksi database gagal | Periksa nilai `DB_HOST`, `DB_PORT`, `DB_USERNAME`, dan `DB_PASSWORD`; pastikan MySQL/MariaDB berjalan. |
| Database/tabel tidak terbuat otomatis | Pastikan pengguna database memiliki izin membuat database dan tabel, atau impor `database.sql` secara manual. |
| Unggah foto/logo gagal | Pastikan `assets/uploads/` ada dan dapat ditulis oleh web server. |
| Kamera tidak bisa dibuka | Akses lewat `localhost` atau HTTPS, lalu izinkan akses kamera di browser. |
| Tampilan tidak berformat | Periksa koneksi internet karena Tailwind CSS dimuat dari CDN. |
| Error `could not find driver` | Aktifkan ekstensi `pdo_mysql` pada `php.ini`, lalu restart server. |

## 📁 Struktur Proyek

```text
.
├── admin/            # Panel admin dan pengelolaan pemilihan
├── assets/           # CSS, JavaScript, dan berkas unggahan
│   └── uploads/      # Foto kandidat dan logo (harus dapat ditulis)
├── config/           # Koneksi dan inisialisasi database
├── docs/
│   └── screenshots/  # Tangkapan layar untuk dokumentasi
├── database.sql      # Skema dan data awal database
├── index.php         # Halaman login
├── login_process.php # Proses login
├── voting.php        # Halaman pemungutan suara
└── vote_process.php  # Proses penyimpanan suara
```

## 🤝 Kontribusi

Kontribusi sangat diterima. Untuk berkontribusi:

1. *Fork* repository ini.
2. Buat branch fitur baru: `git checkout -b fitur/nama-fitur`
3. *Commit* perubahan: `git commit -m "Menambahkan fitur ..."`
4. *Push* ke branch: `git push origin fitur/nama-fitur`
5. Buka *Pull Request*.

Untuk laporan bug atau usulan fitur, silakan buat *issue* baru.

## 🗺️ Rencana Pengembangan

- [ ] Autentikasi admin dengan kata sandi
- [ ] Token atau kode unik sekali pakai untuk pemilih
- [ ] Log aktivitas admin
- [ ] Grafik hasil pemilihan secara langsung
- [ ] Penjadwalan waktu mulai dan selesai pemilihan

## 📄 Lisensi

Tentukan lisensi proyek ini (misalnya MIT) dan tambahkan berkas `LICENSE` di root repository.

---

<div align="center">

Dibuat untuk mendukung pemilihan OSIS yang tertib, transparan, dan efisien. 🎓

</div>