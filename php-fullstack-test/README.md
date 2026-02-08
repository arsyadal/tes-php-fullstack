# PHP Fullstack Test - Client API

REST API untuk manajemen data client dengan Laravel, PostgreSQL, Redis, dan AWS S3.

## Requirements

- PHP 8.1+
- Composer
- PostgreSQL 13+
- Redis Server
- AWS S3 Bucket (untuk storage logo)

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd php-fullstack-test
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

Copy file `.env.example` ke `.env`:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Konfigurasi Database PostgreSQL

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database
DB_USERNAME=postgres
DB_PASSWORD=password_kamu
```

Jalankan migration:

```bash
php artisan migrate
```

### 5. Konfigurasi Redis

Pastikan Redis server sudah berjalan, lalu edit `.env`:

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 6. Konfigurasi AWS S3

Edit `.env` dengan credential AWS:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=nama_bucket
```

Pastikan bucket S3 sudah dikonfigurasi dengan policy yang benar untuk public read.

## Menjalankan Aplikasi

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/clients` | Daftar semua client (pagination) |
| GET | `/clients/{id}` | Detail client by ID |
| GET | `/clients/slug/{slug}` | Detail client by slug |
| POST | `/clients` | Buat client baru |
| PUT | `/clients/{id}` | Update client |
| DELETE | `/clients/{id}` | Hapus client (soft delete) |

### Contoh Request

#### Create Client

```bash
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: multipart/form-data" \
  -F "name=PT Example Indonesia" \
  -F "slug=pt-example-indonesia" \
  -F "client_prefix=EXMP" \
  -F "is_project=0" \
  -F "self_capture=1" \
  -F "client_logo=@/path/to/logo.png"
```

Response:
```json
{
    "success": true,
    "message": "Client berhasil dibuat",
    "data": {
        "id": 1,
        "name": "PT Example Indonesia",
        "slug": "pt-example-indonesia",
        "is_project": "0",
        "self_capture": "1",
        "client_prefix": "EXMP",
        "client_logo": "client-logos/20240115_123456_a1b2c3d4.png",
        "created_at": "2024-01-15T12:34:56.000000Z",
        "updated_at": "2024-01-15T12:34:56.000000Z"
    }
}
```

#### Get All Clients

```bash
curl http://localhost:8000/api/clients
```

#### Get Client by ID

```bash
curl http://localhost:8000/api/clients/1
```

#### Get Client by Slug (Redis Cache)

```bash
curl http://localhost:8000/api/clients/slug/pt-example-indonesia
```

#### Update Client

```bash
curl -X PUT http://localhost:8000/api/clients/1 \
  -H "Content-Type: multipart/form-data" \
  -F "name=PT Example Baru" \
  -F "client_logo=@/path/to/new-logo.png"
```

#### Delete Client

```bash
curl -X DELETE http://localhost:8000/api/clients/1
```

## Struktur Project

```
php-fullstack-test/
├── app/
│   ├── Http/Controllers/
│   │   └── MyClientController.php    # CRUD controller
│   ├── Models/
│   │   └── MyClient.php              # Eloquent model + Redis observers
│   └── Services/
│       ├── RedisService.php          # Redis operations
│       └── S3Service.php             # S3 file operations
├── config/
│   ├── database.php                  # PostgreSQL config
│   ├── filesystems.php               # S3 config
│   └── redis.php                     # Redis config
├── database/migrations/
│   └── 2024_01_15_000001_create_my_client_table.php
├── routes/
│   └── api.php                       # API routes
├── .env.example
├── composer.json
└── README.md
```

## Fitur Utama

### 1. PostgreSQL Database
- Soft delete support
- Timestamps otomatis

### 2. Redis Caching
- Data client disimpan di Redis dengan key = slug
- Persistent storage (tidak expire)
- Auto-sync saat create/update/delete

### 3. AWS S3 Storage
- Upload logo client ke S3
- Auto-delete logo lama saat update
- Generate unique filename

## Catatan

- Semua delete adalah soft delete, data tidak benar-benar dihapus dari database
- Redis key akan otomatis terhapus saat client di-delete
- Logo di S3 tidak otomatis dihapus saat soft delete (bisa dikonfigurasi)
