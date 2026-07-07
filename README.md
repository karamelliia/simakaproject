# Lampiran Source Code Skripsi SIMAKA

Repositori ini memuat pilihan kode sumber penting dari SIMAKA yang berkaitan
langsung dengan empat modul pembahasan skripsi:

1. modul absensi;
2. modul sikap siswa;
3. modul pelanggaran Bimbingan Konseling (BK); dan
4. modul rekomendasi PKL.

Kode modul dikelompokkan ke dalam controller, model, service/support,
konfigurasi, migration, view, route, dan unit test. Berkas aplikasi lain yang
tidak berhubungan langsung dengan keempat modul sengaja tidak disertakan agar
lampiran tetap ringkas.

## Kebutuhan sistem

- PHP 8.4 atau lebih baru (sesuai dependensi yang terkunci pada `composer.lock`)
- Composer
- Node.js dan npm
- MySQL/MariaDB atau basis data lain yang didukung Laravel

## Struktur kode utama

- `app/Http/Controllers/` — alur permintaan pada setiap modul.
- `app/Models/` — model yang digunakan langsung oleh keempat modul.
- `app/Services/` dan `app/Support/` — perhitungan absensi dan rekomendasi PKL.
- `config/rekomendasi_pkl.php` — bobot, penalti, dan kategori rekomendasi.
- `database/migrations/` — struktur tabel khusus keempat modul.
- `resources/views/` — antarmuka keempat modul.
- `tests/Unit/` — pengujian perhitungan absensi dan rekomendasi PKL.

Repositori ini merupakan lampiran terpilih, bukan paket aplikasi mandiri. Kode
aplikasi SIMAKA lengkap tidak disertakan.

## Pengujian

```bash
php artisan test
```

Logika utama rekomendasi PKL berada pada
`app/Services/RekomendasiPklService.php`, dengan parameter penilaian pada
`config/rekomendasi_pkl.php` dan pengujian pada
`tests/Unit/RekomendasiPklServiceTest.php`.

## Privasi data

Repositori ini merupakan snapshot kode sumber untuk dokumentasi skripsi. Dump
basis data, data siswa asli, kredensial, file `.env`, dependensi hasil instalasi,
dan berkas unggahan pengguna tidak disertakan.
