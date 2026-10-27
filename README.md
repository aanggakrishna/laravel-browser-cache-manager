# Laravel Browser Cache Manager

Package Laravel untuk memberi tahu browser bahwa aplikasi sudah diperbarui dan, bila admin mengizinkan, membersihkan beberapa penyimpanan browser yang dipilih. Package ini juga menyediakan tombol kecil mengambang agar pengguna bisa menjalankan pemulihan secara manual.

> **Perlu diketahui:** browser tidak menyediakan cara bagi website untuk menjamin penghapusan seluruh HTTP cache. Redirect `301` yang tersimpan di disk cache dapat tetap digunakan browser. Package ini tidak bisa menjamin penghapusan redirect tersebut.

## Mulai cepat

### 1. Pasang package

Package mendukung PHP 8.1+ dan Laravel 10, 11, atau 12.

```bash
composer require aanggakrishna/laravel-browser-cache-manager
```

Laravel akan menemukan service provider secara otomatis. Publikasikan konfigurasi dan file JavaScript, lalu jalankan migrasi:

```bash
php artisan vendor:publish --tag=browser-cache-config
php artisan vendor:publish --tag=browser-cache-assets
php artisan migrate
```

Konfigurasi tersimpan di `config/browser-cache-manager.php`. File JavaScript dipasang di `public/vendor/browser-cache-manager/`.

### 2. Tampilkan tombol

Tambahkan directive berikut satu kali pada layout Blade utama aplikasi, biasanya di dalam `<head>`:

```blade
@browserCacheManager
```

Tombol perkakas akan muncul di pojok kanan bawah. Pengguna dapat membuka tombol itu, lalu memilih **Bersihkan cache & perbarui**. Setelah proses selesai, package menampilkan ringkasan hasil dan tombol untuk memuat ulang aplikasi.

### 3. Tetapkan versi build aplikasi

Gunakan identitas versi yang berubah setiap kali Anda merilis build aplikasi. Atur nilainya di environment deployment:

```env
BROWSER_CACHE_MANAGER_ENABLED=true
APP_BUILD_VERSION=2026.10.27.1
```

Gunakan nilai `APP_BUILD_VERSION` yang sama pada semua instance/server yang menjalankan build tersebut. Naikkan nilainya untuk build berikutnya. Setelah mengubah environment, ikuti proses deployment aplikasi Anda dan bangun ulang config cache bila digunakan:

```bash
php artisan config:cache
```

Package membandingkan versi build untuk mendeteksi pembaruan. Nilai yang sama di semua instance mencegah browser menerima versi berbeda secara bergantian dan terus memuat ulang.

> **Catatan perintah bump:** `php artisan browser-cache:bump <versi>` mengubah `APP_BUILD_VERSION` di file `.env` yang dapat ditulis. Perintah ini berguna untuk instalasi dengan `.env` lokal. Pada deployment yang mengambil environment dari platform atau secret manager, ubah nilainya di sistem deployment tersebut.

Contoh:

```bash
php artisan browser-cache:bump 2026.10.27.2
```

## Mengatur tombol

Secara default tombol aktif di kanan bawah dengan jarak 20 piksel dari tepi. Ubah bagian `widget` di `config/browser-cache-manager.php`:

```php
'widget' => [
    'enabled' => true,
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'offset_x' => 20,
    'offset_y' => 20,
],
```

Atur `enabled` ke `false` untuk menyembunyikan tombol. Posisi dan jarak juga dapat diubah dari halaman admin jika halaman tersebut diaktifkan.

## Mengaktifkan pemulihan cache

Pembersihan dinonaktifkan secara default agar package tidak menghapus penyimpanan tanpa persetujuan konfigurasi aplikasi. Untuk mengaktifkannya, atur opsi `recovery` di `config/browser-cache-manager.php`.

Contoh yang hanya menghapus Cache Storage bernama `my-app-v3` dan key `my-app:` di localStorage:

```php
'recovery' => [
    'enabled' => true,
    'clear_http_cache' => false,
    'clear_cache_storage' => true,
    'cache_storage_names' => ['my-app-v3'],
    'unregister_service_workers' => false,
    'service_worker_scopes' => [],
    'delete_indexeddb' => false,
    'indexeddb_names' => [],
    'clear_local_storage' => true,
    'local_storage_prefixes' => ['my-app:'],
    'reload_once' => true,
],
```

Isi hanya nama cache, database, scope service worker, dan prefix localStorage yang memang dimiliki aplikasi Anda. Hindari menghapus semua data origin jika aplikasi atau sistem lain menggunakan penyimpanan yang sama.

Opsi berikut dapat diaktifkan terpisah sesuai kebutuhan:

- `clear_cache_storage`: hapus entri Cache Storage bernama yang didaftarkan.
- `unregister_service_workers`: unregister service worker yang scope-nya didaftarkan.
- `delete_indexeddb`: hapus database IndexedDB bernama yang didaftarkan.
- `clear_local_storage`: hapus key localStorage dengan prefix yang didaftarkan.
- `clear_http_cache`: kirim header `Clear-Site-Data: "cache"` ketika endpoint pemulihan dipanggil.

Operasi browser bersifat best effort. Ringkasan tombol melaporkan hasil yang dapat diamati oleh JavaScript, seperti jumlah entri yang dihapus atau kegagalan yang dilaporkan browser. Ringkasan itu bukan bukti bahwa seluruh cache browser telah dibersihkan.

### Tentang HTTP cache dan redirect 301

JavaScript halaman tidak dapat menghapus seluruh HTTP cache browser. Jika `clear_http_cache` diaktifkan, server meminta browser membersihkan cache untuk origin ini dengan header `Clear-Site-Data: "cache"`. Tambahkan juga meta tag CSRF Laravel pada layout agar request POST dapat dikirim:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Dukungan dan perilakunya bergantung pada browser. Permintaan ini dapat menghapus respons cache lain untuk origin yang sama, dan tidak membersihkan cache CDN/proxy.

Browser juga dapat menggunakan redirect `301` yang disimpan lokal tanpa meminta server. Dalam kondisi itu server tidak menerima request pemulihan, sehingga package tidak dapat menghapus redirect tersebut. Pengguna mungkin perlu membersihkan data situs/cache melalui pengaturan browser.

Fitur ini tidak menghapus cookie atau session login. Namun, aplikasi sebaiknya menguji konfigurasi pemulihan di browser yang digunakan sebelum mengaktifkan opsi yang lebih luas.

## Mengaktifkan halaman admin (opsional)

Halaman admin nonaktif secara default. Jika diaktifkan, route berada di `/browser-cache/admin` dan memakai middleware `web`, `auth`, serta Gate `manage-browser-cache` secara default.

Tambahkan Gate tersebut di `App\Providers\AppServiceProvider` atau provider authorization aplikasi:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('manage-browser-cache', function ($user): bool {
        return (bool) $user->is_admin;
    });
}
```

Sesuaikan `is_admin` dengan mekanisme peran aplikasi Anda. Lalu aktifkan halaman admin:

```env
BROWSER_CACHE_ADMIN_ENABLED=true
```

Middleware dapat diubah di `config/browser-cache-manager.php`:

```php
'admin' => [
    'enabled' => (bool) env('BROWSER_CACHE_ADMIN_ENABLED', false),
    'middleware' => ['web', 'auth', 'can:manage-browser-cache'],
],
```

Jangan membuka route admin tanpa autentikasi dan otorisasi yang sesuai. Halaman ini mengubah pengaturan runtime di database.

## Cara polling bekerja

Browser memeriksa endpoint status secara berkala untuk mengetahui versi build dan pengaturan runtime. Interval default adalah 300 detik dan dapat diubah melalui `BROWSER_CACHE_POLL_INTERVAL`. Endpoint status memakai throttle dan respons cache validator agar pemeriksaan berulang tetap ringan.

Pengaturan runtime disimpan di tabel `browser_cache_settings`. Master switch environment `BROWSER_CACHE_MANAGER_ENABLED=false` dapat mematikan package pada deployment tanpa mengandalkan database.

## Menjalankan test

```bash
composer install
composer test
```

## Rilis ke GitHub dan Packagist

Repository: [github.com/aanggakrishna/laravel-browser-cache-manager](https://github.com/aanggakrishna/laravel-browser-cache-manager)

Untuk menerbitkan versi baru:

1. Commit dan push perubahan ke GitHub.
2. Buat tag versi, misalnya `v1.0.1`, lalu push tag tersebut.
3. Pastikan package terdaftar di Packagist dan sinkronkan package bila perlu.
4. Agar Packagist memperbarui data otomatis setiap push/tag, hubungkan webhook GitHub Packagist sesuai petunjuk Packagist.

Composer menggunakan tag versi untuk memilih rilis stabil. Pengguna sebaiknya memasang versi stabil, bukan `dev-main`, untuk aplikasi produksi.

## Lisensi

MIT. Lihat [LICENSE](LICENSE).
