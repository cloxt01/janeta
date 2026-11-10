# JANETA PHP

JANETA (JangkauNet NetOps Assistant) adalah aplikasi internal guna mendukung kegiatan operasional khususnya di manajemen jaringan & perangkatnya.
Fitur utama meliputi pencarian ONT, monitoring port GPON melalui SSH, pengelolaan
OLT, master data ONT, role pengguna, audit log, dan export laporan.

## Kebutuhan

- PHP 8.2:
  `openssl` 
  `mbstring` 
  `pdo_mysql`
  `gd`
  `curl`
  `snmp`
- Composer.
- npm
- MySQL.

## Instalasi

1. Salin `.env.example` menjadi `.env`.
    ```
    sudo cp .env.example .env
    ```
2. Isi koneksi database, kredensial OLT, dan konfigurasi Telegram jika diperlukan.
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=janeta
    DB_USERNAME=<USERNAME-DBMU>
    DB_PASSWORD=<PASSWORD-DBMU>
    ```
3. Buat database MySQL `janeta`.
    ```
    CREATE DATABASE janeta;
    ```
4. Jalankan:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
npm.cmd install
npm.cmd run build
```

## Langkah terakhir
1. Download Certificate melalui situs resmi [klik disini]

2. Di dalam `php.ini` atur :

  - `curl.cainfo = /path/to/cacert.pem`
  - `openssl.cafile = /path/to/cacert.pem`



## Konfigurasi `.env`

Konfigurasi Telegram dan alarm:

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_API_URL=https://api.telegram.org
TELEGRAM_POLLING_TIMEOUT=30
JANETA_ALARM_INTERVAL=60
JANETA_ALARM_CHAT_ID=
```

Konfigurasi aplikasi dipusatkan di [config/janeta.php](config/janeta.php).

## Menjalankan aplikasi

Untuk development:

```powershell
php artisan serve --host=0.0.0.0 --port=80
npm.cmd run build # atau `npm run dev`
```

Buka aplikasi melalui :

https://domainmu.com

Login menggunakan akun yang kamu set di seeder `database\seeders\DatabaseSeeder.php`: 



## Dashboard

Setelah login, menu yang tersedia:

- **Dashboard**: ringkasan OLT, mapping, whitelist, dan user.
- **Cari ONT** (`/dashboard/ont`): cari ID customer dan tampilkan lokasi,
  serial number, status, RX/TX power, suhu, voltage, serta data WAN.
- **OLT** (`/dashboard/olts`): tambah, edit, aktif/nonaktifkan, test koneksi,
  dan hapus OLT. Password lama tidak ditampilkan dan tidak berubah jika field
  password edit dikosongkan.
- **Port Monitor** (`/dashboard/olts/ports`): pilih OLT, frame, dan slot lalu
  scan 16 port GPON melalui SSH. Port ditampilkan sebagai online, LOS, offline,
  atau inactive.
- **Master Data** (`/dashboard/mappings`): mapping merek, tipe, WLAN band,
  serta export CSV, Excel, dan PDF.
- **Telegram**: kelola whitelist chat.
- **Pengguna**: kelola akun dan role.
- **Audit Log**: lihat aktivitas login dan perubahan data.

Role disimpan pada kolom `users.hris_role` dengan nilai:

- `super_admin`
- `admin`
- `operator`
- `viewer`

Hak akses menu admin saat ini tetap menggunakan flag `users.is_admin`.

## Bot Telegram dan alarm

Jalankan bot Telegram long polling sebagai proses terpisah:

```powershell
php artisan janeta:telegram
```

```powershell
php artisan janeta:alarms
```

Command alarm membaca alarm aktif dari OLT dan mengirim notifikasi LOS atau
dying-gasp ke `JANETA_ALARM_CHAT_ID`. Pastikan command ini dijalankan sebagai
worker/service terpisah dan dihentikan dengan aman saat deployment.

### SSL Telegram pada Windows


## REST API

- `GET /api/v1/ont/check-desc?id=ID_CUSTOMER`
- `GET /api/v1/ont/version?id=ID_CUSTOMER`


Maintained by [@cloxt01](https://github.com/cloxt01)