# Government Document Eligibility System - DLH

Sistem Pendokumentasian Permohonan Dokumen Kelayakan Lingkungan Hidup Dinas Lingkungan Hidup (DLH).

Project ini merupakan prototype sistem pengajuan kelayakan dokumen berskala enterprise, aman, dan berkinerja tinggi.

---

## 1. Stack Teknologi

- **Backend**: Laravel 12.x, PHP 8.2+
- **Frontend**: Vue 3 (Composition API `<script setup>`), Pinia (State Management), Vue Router
- **Database**: PostgreSQL 17
- **Caching & Queue**: Redis 8
- **Styling**: Tailwind CSS v4.0

---

## 2. Fitur Utama

1. **Autentikasi Multi-Peran**: Pemohon (`applicant`) dan Penilai (`reviewer`) terintegrasi menggunakan Laravel Sanctum & Spatie Laravel Permission.
2. **State-Machine Workflow**:
   - Status: `draft` -> `submitted` -> `under_review` -> (`revision_required` -> `submitted`) ATAU `approved` / `rejected`.
   - Transisi status terisolasi secara transaksional di level service.
3. **Upload Dokumen Aman**:
   - Disimpan di private storage (`storage/app/private`).
   - Validasi MIME-type asli via `finfo` (binary signature check).
   - UUID filename generation untuk mencegah penyingkapan data sensitif.
4. **Dashboard Evaluasi**:
   - Agregasi data dengan sub-query SQL optimal (single-query count).
   - Redis caching selama 300 detik dengan invalidasi otomatis (bust) saat transisi status.
   - Monthly submission trend chart (ApexCharts) untuk penilai.
5. **Audit Log & Timeline**: Setiap perubahan status dan catatan dicatat secara kronologis.
6. **Optimasi Performa**:
   - Database indexing pada foreign keys, status, created_at, dan composite index `(user_id, status)`.
   - Menggunakan cursor pagination (`cursorPaginate()`) untuk menghindari degradasi query pada deep page.

---

## 3. Akun Demo Uji Coba

Gunakan kredensial berikut untuk masuk ke sistem:

- **Pemohon (Applicant)**:
  - Email: `applicant@example.com`
  - Password: `password`
- **Penilai (Reviewer)**:
  - Email: `reviewer@example.com`
  - Password: `password`

---

## 4. Cara Instalasi & Setup

### Prasyarat
- PHP 8.2 atau lebih tinggi
- Composer
- Node.js & npm
- Docker (untuk PostgreSQL & Redis)

### Langkah 1: Kloning & Install Dependensi
```bash
# Install PHP dependencies
composer install

# Install Javascript dependencies
npm install --legacy-peer-deps
```

### Langkah 2: Konfigurasi Environment (`.env`)
Salin berkas `.env.example` ke `.env` dan sesuaikan koneksi database PostgreSQL & Redis:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=postgres
DB_PASSWORD=postgres

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Langkah 3: Migrasi & Database Seeder (10k Data Uji)
Jalankan migrasi fresh beserta seeder untuk men-generate data uji (10.000 project, 1.000 applicant, 1.000 reviewer):
```bash
php artisan migrate:fresh --seed
```

### Langkah 4: Menjalankan Server Aplikasi
Jalankan server Laravel backend dan Vite frontend secara paralel:
```bash
# Menjalankan backend server & frontend build watcher
npm run dev
```

---

## 5. Dokumentasi API (v1)

Semua endpoint dilindungi Bearer Token Sanctum:

| Method | Endpoint | Peran | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/auth/register` | Public | Pendaftaran akun applicant baru |
| `POST` | `/api/v1/auth/login` | Public | Login dan generate API Token |
| `POST` | `/api/v1/auth/logout` | Auth | Menghapus token saat ini |
| `GET` | `/api/v1/auth/me` | Auth | Mengambil profil user aktif |
| `GET` | `/api/v1/dashboard` | Auth | Mendapatkan agregasi metrics dashboard |
| `GET` | `/api/v1/projects` | Auth | Mendapatkan daftar projects (cursor paginated) |
| `POST` | `/api/v1/projects` | Applicant | Membuat permohonan baru + upload file |
| `GET` | `/api/v1/projects/{project}` | Auth | Melihat rincian permohonan & history logs |
| `PUT` | `/api/v1/projects/{project}` | Applicant | Memperbaiki permohonan (status draft/revision) |
| `DELETE` | `/api/v1/projects/{project}` | Applicant | Menghapus permohonan (hanya status draft) |
| `GET` | `/api/v1/projects/{project}/documents/{document}` | Auth | Mengunduh file berkas secara aman |
| `GET` | `/api/v1/projects/{project}/history` | Auth | Melihat riwayat approval log kronologis |
| `POST` | `/api/v1/projects/{project}/submit` | Applicant | Mengirim permohonan ke penilai |
| `POST` | `/api/v1/projects/{project}/reviews` | Reviewer | Mulai meninjau permohonan |
| `POST` | `/api/v1/projects/{project}/revision` | Reviewer | Meminta revisi dengan catatan perbaikan |
| `POST` | `/api/v1/projects/{project}/approve` | Reviewer | Menyetujui dokumen kelayakan |
| `POST` | `/api/v1/projects/{project}/reject` | Reviewer | Menolak dokumen kelayakan |

---

## 6. Struktur Database (ERD Concept)

- **users**: `id` (UUID, PK), `name`, `email`, `password`, `created_at`, `updated_at`, `deleted_at`.
- **projects**: `id` (UUID, PK), `user_id` (UUID, FK -> users), `title`, `description`, `status` (indexed), `created_at` (indexed), `updated_at`.
- **documents**: `id` (UUID, PK), `project_id` (UUID, FK -> projects), `filename`, `original_name`, `file_path`, `file_size`, `mime_type`, `created_at`, `updated_at`.
- **approval_logs**: `id` (UUID, PK), `project_id` (UUID, FK -> projects), `user_id` (UUID, FK -> users), `action`, `old_status`, `new_status`, `notes`, `created_at` (indexed).

---

## 7. Pengujian (Testing)

Sistem dilengkapi dengan feature testing komprehensif (17 test cases, 50 assertions) yang menguji seluruh alur autentikasi, IDOR prevention, state transitions, upload validation, dan audit logs.

Jalankan pengujian:
```bash
php artisan test
```

---
