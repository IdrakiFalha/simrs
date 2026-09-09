# Aplikasi SIMRS RSUD Meuraxa

Aplikasi Sistem Informasi Manajemen Rumah Sakit (SIMRS) berbasis Web dengan arsitektur MVC sederhana (Native PHP). Terintegrasi dengan fitur SATUSEHAT dan Audit Trail.

## Persyaratan Sistem
- PHP 8.1 atau lebih baru (Disarankan PHP 8.3)
- Web Server (Apache/Nginx/Laragon/XAMPP)
- MySQL / MariaDB

## Cara Instalasi di Komputer Lain (Bagi Teman)

1. **Clone/Download** *repository* ini ke dalam folder server lokal (misalnya `htdocs` untuk XAMPP atau `www` untuk Laragon).
2. Nyalakan **Apache** dan **MySQL** dari aplikasi XAMPP/Laragon.
3. Buka aplikasi manajemen *database* (phpMyAdmin / HeidiSQL).
4. Buat *database* baru bernama `simrs` (atau nama lain, lalu sesuaikan di `config/database.php`).
5. **Import file** `database/database.sql` atau `database/schema.sql` ke dalam *database* yang baru dibuat.
6. Buka `config/database.php` dan pastikan konfigurasi *database* (username dan password) sesuai dengan pengaturan di komputermu (biasanya username `root` dan password kosong).
7. Buka browser dan jalankan aplikasi lewat `http://localhost/simrs`.

## Akun Demo
Gunakan akun ini untuk masuk ke dalam sistem:
- **Username:** admin
- **Password:** admin123
