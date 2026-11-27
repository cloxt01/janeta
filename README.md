# JANETA PHP

**JANETA (JangkauNet NetOps Assistant)** adalah aplikasi internal untuk mendukung kegiatan operasional Network Operations, khususnya pengelolaan dan monitoring jaringan serta perangkatnya.

> Status rilis: **Development**

## Fitur

- **Audit Log**: melacak aktivitas pengguna (login dan perubahan data).
- **OLT Activity**: mencatat dan menyimpan log aktivitas yang menggunakan layanan OLT.
- **Mikrotik Activity**: mencatat dan menyimpan log Mikrotik.
- **Live Location**: melacak lokasi pengguna.
- **User Management & Access Control**: mengelola akun, role, dan hak akses.
- **Port Monitoring**: menampilkan status up/down port GPON OLT melalui SSH.
- **ONT Lookup (Interactive)**: menampilkan detail ONT dengan fitur interaktif.
- **Master Data**: mapping merek, tipe, WLAN band, serta export CSV, Excel, dan PDF.
- **Bot Telegram & Alarm**: notifikasi LOS / dying-gasp dari OLT.
- **Realtime Update**: OLT Activity dan Mikrotik Activity diperbarui langsung lewat WebSocket (Laravel Reverb).

## Kebutuhan

- PHP 8.2 dengan ekstensi: `openssl`, `mbstring`, `sockets`, `pdo_mysql`, `gd`, `curl`, `snmp`
- Composer
- Node.js dan npm
- MySQL
- Web server (Nginx atau Apache)

## Instalasi

1. Salin `.env.example` menjadi `.env`.

    ```bash
    cp .env.example .env
    ```

2. Isi koneksi database, kredensial OLT, dan konfigurasi Telegram jika diperlukan.

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=janeta
    DB_USERNAME=<USERNAME-DBMU>
    DB_PASSWORD=<PASSWORD-DBMU>
    ```

3. Buat database MySQL.

    ```sql
    CREATE DATABASE janeta;
    ```

4. Jalankan:

    ```bash
    composer install
    php artisan key:generate
    php artisan migrate --seed
    npm install
    npm run build
    ```

   > Di Windows PowerShell gunakan `npm.cmd` sebagai pengganti `npm`.

5. Download sertifikat CA (`cacert.pem`) dari situs resmi curl, lalu atur di `php.ini`:

    ```ini
    curl.cainfo = /path/to/cacert.pem
    openssl.cafile = /path/to/cacert.pem
    ```

## Konfigurasi `.env`

Konfigurasi aplikasi dipusatkan di [config/janeta.php](config/janeta.php).

> Ganti semua nilai berformat `<PLACEHOLDER>` dengan nilai sesuai environment kamu.

### URL aplikasi

```dotenv
APP_URL=https://<APP_DOMAIN>
```

### Telegram dan alarm

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_API_URL=https://api.telegram.org
TELEGRAM_POLLING_TIMEOUT=30
JANETA_ALARM_INTERVAL=60
JANETA_ALARM_CHAT_ID=
```

### Realtime (Laravel Reverb)

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# Alamat publik yang dipakai browser (lewat Nginx, SSL ditangani Nginx)
REVERB_HOST=<APP_DOMAIN>
REVERB_PORT=443
REVERB_SCHEME=https

# Alamat server Reverb (internal, di belakang Nginx)
REVERB_SERVER_HOST=<REVERB_SERVER_HOST>
REVERB_SERVER_PORT=<REVERB_SERVER_PORT>

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> Variabel `VITE_*` dibakar saat build. Setiap kali diubah, jalankan ulang `npm run build` lalu hard refresh browser.

## Menjalankan aplikasi

### Development

```bash
php artisan serve --host=0.0.0.0 --port=80
npm run dev        # atau: npm run build
php artisan reverb:start --debug
```

### Production

Jalankan sebagai service/worker terpisah (systemd, Supervisor, atau NSSM di Windows):

```bash
php artisan reverb:start --host=0.0.0.0 --port=<REVERB_SERVER_PORT>
php artisan queue:work --queue=router,olt
php artisan schedule:work
php artisan janeta:telegram
php artisan janeta:alarms
```

Detail scheduler dan queue ada di bagian [Scheduler dan Queue](#scheduler-dan-queue).

Hentikan dengan aman saat deployment, dan restart Reverb setiap ada perubahan `.env`:

```bash
php artisan config:clear
```

Contoh service systemd untuk Reverb (`/etc/systemd/system/reverb.service`):

```ini
[Unit]
Description=Laravel Reverb
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/janeta
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=<REVERB_SERVER_PORT>
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

## Konfigurasi Nginx (HTTPS + WebSocket)

Contoh server block:

```nginx
server {
    listen 80;
    server_name <APP_DOMAIN>;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name <APP_DOMAIN>;

    ssl_certificate     /path/to/fullchain.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root  /path/to/janeta/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP (FastCGI / PHP-FPM)
    location ~ \.php$ {
        fastcgi_pass   <PHP_FPM_HOST>:<PHP_FPM_PORT>;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    # WebSocket (Laravel Reverb)
    location /app/ {
        proxy_pass http://<REVERB_SERVER_HOST>:<REVERB_SERVER_PORT>;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }

    # HTTP API Reverb
    location /apps/ {
        proxy_pass http://<REVERB_SERVER_HOST>:<REVERB_SERVER_PORT>;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Catatan penting:

- Header `Connection` harus berisi literal `"upgrade"` (atau `$connection_upgrade` dari blok `map`).
- Komentar di Nginx memakai `#`, bukan `//`.

### Tes WebSocket

```bash
curl -i -N --max-time 5 -k \
  -H "Connection: Upgrade" -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" -H "Sec-WebSocket-Key: x3JJHMbDL1EzLkh9GBhXDw==" \
  https://<APP_DOMAIN>/app/<REVERB_APP_KEY>
```

Hasil yang benar: `HTTP/1.1 101 Switching Protocols` dan event `pusher:connection_established`.

## Dashboard

Setelah login, menu yang tersedia:

- **Dashboard**: ringkasan OLT, mapping, whitelist, dan user.
- **Cari ONT** (`/dashboard/ont`): cari ID customer dan tampilkan lokasi, serial number, status, RX/TX power, suhu, voltage, serta data WAN.
- **OLT** (`/dashboard/olts`): tambah, edit, aktif/nonaktifkan, test koneksi, dan hapus OLT. Password lama tidak ditampilkan dan tidak berubah jika field password saat edit dikosongkan.
- **Port Monitor** (`/dashboard/olts/ports`): pilih OLT, frame, dan slot lalu scan 16 port GPON melalui SSH. Port ditampilkan sebagai online, LOS, offline, atau inactive.
- **Master Data** (`/dashboard/mappings`): mapping merek, tipe, WLAN band, serta export CSV, Excel, dan PDF.
- **Telegram**: kelola whitelist chat.
- **Pengguna**: kelola akun dan role.
- **Audit Log**: lihat aktivitas login dan perubahan data.
- **OLT Activity** dan **Mikrotik Activity**: log aktivitas dengan pembaruan realtime.

### Role

Role disimpan pada kolom `users.hris_role` dengan nilai:

- `super_admin`
- `admin`
- `operator`
- `viewer`

Hak akses menu admin saat ini tetap menggunakan flag `users.is_admin`.

## Bot Telegram dan alarm

Bot Telegram (long polling):

```bash
php artisan janeta:telegram
```

Command alarm membaca alarm aktif dari OLT dan mengirim notifikasi LOS atau dying-gasp ke `JANETA_ALARM_CHAT_ID`:

```bash
php artisan janeta:alarms
```

Pastikan kedua command ini dijalankan sebagai worker/service terpisah.

### SSL Telegram pada Windows

Jika request ke Telegram gagal karena sertifikat, pastikan `curl.cainfo` dan `openssl.cafile` di `php.ini` sudah mengarah ke `cacert.pem` (lihat langkah instalasi nomor 5), lalu restart PHP.

## Scheduler dan Queue

Task terjadwal didefinisikan di `routes/console.php`:

| Task | Interval | Keterangan |
|---|---|---|
| `janeta:collect-snmp` | Setiap 5 menit | Mengumpulkan data SNMP. Output ditulis ke `storage/logs/collect-snmp.log`. |
| `janeta-mikrotik-log-collector` | Setiap 1 menit | Mengirim job `CollectMikrotikLogs` ke queue `router` untuk setiap router Mikrotik yang `is_active` dan `api_enabled`. |

### Menjalankan scheduler

Development:

```bash
php artisan schedule:work
```

Production (Linux), tambahkan cron yang memanggil scheduler tiap menit:

```cron
* * * * * cd /path/to/janeta && php artisan schedule:run >> /dev/null 2>&1
```

Production (Windows): jalankan `php artisan schedule:work` sebagai service (misalnya NSSM), atau buat Task Scheduler yang memanggil `php artisan schedule:run` setiap menit.

### Menjalankan queue worker

Aplikasi memakai queue bernama berikut:

| Queue | Job | Keterangan |
|---|---|---|
| `olt` | `ProcessOltServices` | Memproses layanan OLT. |
| `router` | `CollectMikrotikLogs` | Mengumpulkan log Mikrotik (dikirim scheduler tiap 5 detik). |

Worker harus mendengarkan semua queue tersebut:

```bash
php artisan queue:work --queue=olt,router
```

Tanpa worker ini, log Mikrotik dan job OLT tidak akan diproses walaupun scheduler berjalan. Pastikan juga di `.env`:

```dotenv
QUEUE_CONNECTION=database   # atau redis; jangan sync
```


```bash
php artisan queue:restart
```

Contoh service systemd (`/etc/systemd/system/janeta-queue.service`):

```ini
[Unit]
Description=JANETA Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/janeta
ExecStart=/usr/bin/php artisan queue:work --queue=router,olt --sleep=1 --tries=1 --timeout=60
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

> Task Mikrotik mencatat baris log `info` per router setiap 5 detik. Pantau ukuran `storage/logs/laravel.log`, dan gunakan driver log `daily` atau turunkan level log di production.

## Troubleshooting

| Gejala | Penyebab umum |
|---|---|
| Reverb tidak connect di browser | Build frontend lama atau cache. Jalankan `npm run build`, lalu hard refresh. |
| `400 Connection Upgrade MUST be requested` | Header `Connection` di Nginx salah (lihat bagian Nginx). |
| `502` pada `/app/` | Reverb belum jalan atau `<REVERB_SERVER_HOST>:<REVERB_SERVER_PORT>` tidak terjangkau dari Nginx. |
| WebSocket `101` lalu langsung putus | `allowed_origins` di `config/reverb.php` atau `REVERB_APP_KEY` tidak cocok. |
| Channel private gagal subscribe | Cek request `broadcasting/auth` (419/403/500) di tab Network. |
| Request ke Telegram gagal (SSL) | `cacert.pem` belum diatur di `php.ini`. |
| Log Mikrotik tidak bertambah | Scheduler atau queue worker belum jalan, atau worker tidak mendengarkan queue `router`. |
| Job OLT tidak diproses | Worker tidak mendengarkan queue `olt`. |
| Job menumpuk di tabel `jobs` | Queue worker mati. Jalankan ulang `queue:work --queue=router,olt`. |
| Data SNMP tidak update | Scheduler belum jalan. Cek `storage/logs/collect-snmp.log`. |

---

Maintained by [@cloxt01](https://github.com/cloxt01)
