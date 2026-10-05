# Quality Inspection – Backend (Laravel 11 + MySQL / phpMyAdmin)

Project Laravel lengkap, sudah dikonfigurasi untuk XAMPP/Laragon + phpMyAdmin.
Satu-satunya yang belum ada adalah folder `vendor/` — dibuat otomatis oleh `composer install`.

## Prasyarat
PHP 8.2+ (XAMPP terbaru sudah cukup), Composer, MySQL/MariaDB (XAMPP).
Ekstensi PHP yang harus aktif (default XAMPP sudah): `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`.

## Langkah
1. Nyalakan **Apache** dan **MySQL** di XAMPP.
2. Buka http://localhost/phpmyadmin → buat database kosong **QualityInspectionDb** (collation `utf8mb4_unicode_ci`).
3. Di folder project:
   ```
   composer install
   php artisan migrate
   php artisan storage:link
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   Tiga tabel akan muncul di phpMyAdmin. (Alternatif tanpa artisan migrate: phpMyAdmin → Import → `database/quality_inspection_mysql.sql`.)
4. Tes: buka http://localhost:8000/api/inspections. Response sukses menggunakan format standar:
   ```json
   {
     "status": "success",
     "message": "Data inspeksi berhasil diambil.",
     "data": []
   }
   ```
   Error juga memakai `status`, `message`, dan `data`; pada error validasi, `data.errors` berisi detail per field.

`.env` sudah berisi `root` tanpa password (default XAMPP) dan APP_KEY. Ubah `DB_USERNAME/DB_PASSWORD` bila berbeda.

## Menyambung dari iOS
- Simulator: `http://localhost:8000`
- iPhone fisik: `http://<IP-laptop>:8000` (satu Wi-Fi; izinkan HTTP di Info.plist/ATS untuk development).
- Field `url` hasil upload berupa path relatif (`/storage/uploads/...`); gabungkan dengan base URL saat menampilkan/mengunduh.

## Endpoint (prefix `/api`)
| Method | URL | Fungsi |
|---|---|---|
| GET | `/inspections[?since=ISO8601]` | List transaksi (digabung per TransactionId) |
| GET | `/inspections/{transactionId}` | Detail + foto + pinpoint |
| POST | `/inspections/submit` | Submit; tiap temuan = 1 row di `Th_QualityInspection` |
| PUT | `/inspections/{transactionId}` | Update `picTotal` & `vendor` |
| DELETE | `/inspections/{transactionId}` | Hapus (soft delete, `Status=0`) |
| POST | `/files/upload?kind=drawing\|picture` | Upload PDF denah / foto (multipart, field `file`) |

## Alur offline-first
1. iOS simpan lokal dengan `transactionId` (UUID) + `clientId` (UUID) tiap temuan, status `pendingSync`.
2. Saat online: upload file → dapat `url`; lalu `POST /inspections/submit`. Aman di-retry (idempotent per `transactionId`).
3. Update/hapus juga diantri lokal lalu dikirim via `PUT`/`DELETE`.

Contoh body submit:
```json
{
  "transactionId": "7d1c1e0e-0a53-4c9a-9a0e-2f6a3c0a1111",
  "tower": "A", "lantai": "3", "zona": "Zona 1",
  "drawingUrl": "/storage/uploads/drawings/abc.pdf",
  "picTotal": "Budi", "mk": "MK Sejahtera", "vendor": "PT Vendor",
  "findings": [{
    "clientId": "11111111-2222-3333-4444-555555555555",
    "ketidaksesuaian": "Celah tidak sama", "descTemuan": "Retak di dinding",
    "pictures": [{ "pictureUrl": "/storage/uploads/pictures/x.jpg", "keterangan": "Foto 1" }],
    "pinpoints": [{ "drawingUrl": "/storage/uploads/drawings/abc.pdf", "keterangan": "Titik A",
                    "pinpointX": 0.4213, "pinpointY": 0.7788, "pageNumber": 1 }]
  }]
}
```
Koordinat pinpoint = rasio 0–1 terhadap lebar/tinggi halaman PDF. Field tambahan di luar ERD: `TransactionId`, `ClientId`, `CreatedAt`, `UpdatedAt`.

## Catatan
Soal menyebut SQL Server. Project ini memakai MySQL karena phpMyAdmin; untuk pindah ke SQL Server cukup ubah `DB_CONNECTION=sqlsrv` di `.env` (butuh ekstensi `pdo_sqlsrv`) lalu `php artisan migrate`.
