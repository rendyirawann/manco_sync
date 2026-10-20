# MancoSync — Panduan Deploy ke Server

Panduan memasang MancoSync (Laravel 12 + Octane/RoadRunner, ditambah layanan Rust dan Node) di server Ubuntu 24.04 dengan **nginx + systemd**.

Isinya disamakan dengan server yang sedang berjalan (`76.13.23.58`, Oktober 2026). Kalau ada yang berbeda dengan server baru, ikuti panduan ini lalu sesuaikan.

- **Repository:** `git@github.com:rendyirawann/manco_sync.git` (branch `main`)
- **Domain:** `https://manco.hustlesync.my.id`, juga lewat path `https://beoulve-dev.biz.id/manco-sync/`
- **Folder aplikasi:** `/var/www/html/manco-sync`
- **Layanan Node pendamping:** `/var/www/manco-node/` (anime-api, tmdb-embed-api, `portal-health.sh`)
- **User proses:** `www-data`

Semua perintah dijalankan sebagai root, kecuali yang memakai `sudo -u www-data`. Ganti setiap `GANTI_*` dengan nilai asli. Jangan menyalin password ke dokumen ini.

---

## 1. Arsitektur dan port

Semua layanan hanya mendengarkan `127.0.0.1`. Hanya nginx yang terbuka ke internet.

| Komponen | Unit systemd | Port | Fungsi |
|---|---|---|---|
| Laravel (Octane, RoadRunner) | `mancosync-octane` | 8047 | Web, portal, proxy gambar `/portal/img` |
| Reverb (WebSocket) | `mancosync-reverb` | 8087 | Realtime |
| Queue worker | `mancosync-queue` | – | Antrean (driver `database`) |
| Scheduler | `mancosync-schedule.timer` | – | `artisan schedule:run` tiap menit |
| Rust API | `mancosync-rust` | 8101 | Sync engine data manga/chapter |
| anime-api (Node) | `manco-anime-api` | 8103 | Scraper anime, drama, novel, Video AI, MangaDex |
| tmdb-embed-api (Node) | `manco-tmdb-embed` | 8787 | Stream film (TMDB) |
| Pemeriksa sumber | `manco-portal-health.timer` | – | `artisan portal:health` (jadwal di §9) |
| Pembersih cache gambar | `manco-imgcache-clean.timer` | – | Hapus cache gambar > 7 hari, tiap 03:30 |
| PostgreSQL 16 | `postgresql` | 5432 | Database `manco_sync` |
| Redis 7 (khusus) | `redis-beoulve` | 6381 | Cache dan session (DB 9) |
| MongoDB 8 | `mongod` | 27017 | Dipakai `mancosync-rust` |

Sumber eksternal yang dipanggil server:

- **Sanka API** (`https://www.sankavollerei.web.id`): komik (komiku, mangasusuku, dan lainnya) dan sebagian anime. Lihat §11 sebelum go-live.
- **TMDB** (`TMDB_API_KEY`) dan **OpenSubtitles** (`OPENSUBTITLES_API_KEY`): film dan subtitle.

## 2. Paket

```bash
apt update
# PHP 8.4 dari PPA ondrej (Ubuntu 24.04 bawaan hanya 8.3)
add-apt-repository -y ppa:ondrej/php && apt update
apt install -y php8.4-cli php8.4-fpm php8.4-pgsql php8.4-mysql php8.4-redis php8.4-igbinary \
    php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath \
    php8.4-sqlite3 php8.4-sockets php8.4-opcache unzip git
composer --version || (curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer)

# Node.js 22 (anime-api, tmdb-embed-api, build aset Vite)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt install -y nodejs

# Database dan cache
apt install -y postgresql redis-server nginx certbot python3-certbot-nginx
# MongoDB 8: ikuti repo resmi mongodb.org untuk Ubuntu 24.04, lalu:
systemctl enable --now mongod

# Rust (build manco-rust)
curl https://sh.rustup.rs -sSf | sh -s -- -y && . "$HOME/.cargo/env"
```

GD harus mendukung WebP dan JPEG, karena proxy gambar mengecilkan halaman manga: `php8.4 -r 'var_dump(gd_info());'`.

Kalau PHP lain sudah terpasang dan dipakai app lain, **jangan** ubah `php` default. MancoSync selalu dipanggil lewat `/usr/bin/php8.4`.

## 3. PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE ROLE rend1 WITH LOGIN PASSWORD 'GANTI_PASSWORD_DB';
CREATE DATABASE manco_sync OWNER rend1 ENCODING 'UTF8';
SQL
```

**Pindah dari server lama:** salin data, jangan migrate dari nol.

```bash
# di server lama
sudo -u postgres pg_dump -Fc manco_sync > /tmp/manco_sync.dump
# di server baru (setelah file disalin)
sudo -u postgres pg_restore --no-owner --role=rend1 -d manco_sync /tmp/manco_sync.dump
```

## 4. Redis khusus (port 6381)

MancoSync memakai instance Redis sendiri agar tidak bercampur dengan app lain.

`/etc/redis/redis-beoulve.conf`, cukup isi baris berikut di atas config bawaan:

```
port 6381
bind 127.0.0.1 -::1
maxmemory 1gb
maxmemory-policy volatile-lru
save 900 1
save 300 10
save 60 10000
```

Unit `/etc/systemd/system/redis-beoulve.service` disalin dari `redis-server.service`, dengan `Type=notify`, `User=redis`, dan `ExecStart=/usr/bin/redis-server /etc/redis/redis-beoulve.conf --supervised systemd --daemonize no`. Lalu jalankan `systemctl enable --now redis-beoulve`.

## 5. Kode Laravel

```bash
mkdir -p /var/www/html && cd /var/www/html
git clone git@github.com:rendyirawann/manco_sync.git manco-sync
cd manco-sync
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
./vendor/bin/rr get-binary          # menaruh ./rr (RoadRunner) di folder proyek
npm ci && npm run build             # public/build tidak ada di repo
```

### `.env`

Salin `.env` dari server lama (cara tercepat), atau buat dari `.env.example`. Nilai yang harus benar:

| Variabel | Isi di server saat ini | Catatan |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | |
| `APP_URL` | `https://beoulve-dev.biz.id/manco-sync` | Sesuaikan dengan domain utama |
| `DB_*` | `pgsql`, `127.0.0.1`, `5432`, `manco_sync`, `rend1` | Password dari §3 |
| `REDIS_CLIENT` / `REDIS_PORT` | `phpredis` / `6381` | `REDIS_DB` dan `REDIS_CACHE_DB` = `9` |
| `CACHE_STORE` / `SESSION_DRIVER` | `redis` / `redis` | `CACHE_PREFIX=manco_cache` |
| `QUEUE_CONNECTION` | `database` | |
| `OCTANE_SERVER` | `roadrunner` | |
| `BROADCAST_CONNECTION` | `reverb` | `REVERB_SERVER_PORT=8087`, `REVERB_PORT=443`, `REVERB_SCHEME=https`, `REVERB_HOST` = domain publik |
| `SANKA_BASE` | `https://www.sankavollerei.web.id` | |
| `TMDB_EMBED_BASE` | `http://127.0.0.1:8787` | |
| `MANCO_RUST_BASE` | `http://127.0.0.1:8101` | |
| `TMDB_API_KEY`, `OPENSUBTITLES_API_KEY`, `DRACIN_API_KEY`, `MAIL_*` | rahasia | Salin dari server lama |
| `APP_KEY` | rahasia | **Sama** dengan server lama, agar session dan data terenkripsi tetap terbaca |

### Cache, izin, storage

```bash
cd /var/www/html/manco-sync
sudo -u www-data php8.4 artisan storage:link
install -d -o www-data -g www-data -m 775 storage/app/imgcache
chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php8.4 artisan config:cache
sudo -u www-data php8.4 artisan view:cache
sudo -u www-data php8.4 artisan event:cache
```

> **Jangan jalankan `php artisan route:cache`** (juga `optimize`, yang ikut membuat route cache). Route cache merusak manco-sync. Kalau terlanjur: `php8.4 artisan route:clear`.

Config di-cache, jadi setiap kali `.env` diubah jalankan ulang `config:cache` lalu `systemctl reload mancosync-octane`.

## 6. Rust API

```bash
cd /var/www/html/manco-sync/manco-rust
cargo build --release               # hasil: target/release/manco-rust
```

Koneksinya diatur lewat environment di unit systemd (§8), tidak dari `.env` Laravel.

## 7. Layanan Node

```bash
mkdir -p /var/www/manco-node && cd /var/www/manco-node
git clone https://github.com/yogasungkowo/anime-api.git
git clone https://github.com/Inside4ndroid/TMDB-Embed-API tmdb-embed-api
(cd anime-api && npm ci && npm run build)        # hasil: dist/index.js
(cd tmdb-embed-api && npm ci)
chown -R www-data:www-data /var/www/manco-node
```

Kedua folder di server saat ini **berisi perubahan lokal** di atas repo aslinya (scraper novel, MangaDex, dan lainnya). Untuk hasil yang sama persis, salin foldernya dari server lama dengan `rsync -a`, jangan clone ulang.

`/var/www/manco-node/portal-health.sh` (dipanggil timer pemeriksa sumber):

```sh
#!/bin/sh
APP=/var/www/html/manco-sync
cd "$APP" || exit 0
exec 9>/tmp/portal-health.lock; flock -n 9 || exit 0
run() { nice -n 15 ionice -c3 /usr/bin/php8.4 artisan portal:health "$@" >> "$APP/storage/logs/portal-health.log" 2>&1; }
if [ -f "$APP/storage/app/portal-health.request" ]; then run --if-requested; exit 0; fi
M=$(date +%-M); H=$(date +%-H)
# Sekali sehari saja, 04:07, mode cepat (lihat §11 soal beban ke sumber).
# Pemeriksaan kapan saja: tombol "Cek & Perbaiki" (superadmin).
if [ "$M" = 7 ] && [ "$H" = 4 ]; then run; fi
```

## 8. systemd

Semua file di `/etc/systemd/system/`. Setelah membuat atau mengubah: `systemctl daemon-reload`.

**`mancosync-octane.service`**

```ini
[Unit]
Description=MancoSync Laravel (Octane/RoadRunner)
After=network.target redis-beoulve.service
StartLimitIntervalSec=0

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/manco-sync
ExecStart=/usr/bin/php8.4 artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8047 --workers=24 --max-requests=500
ExecReload=/usr/bin/php8.4 artisan octane:reload
ExecStop=/usr/bin/php8.4 artisan octane:stop
Restart=always
RestartSec=3
KillMode=process

[Install]
WantedBy=multi-user.target
```

`--workers=24` disengaja, bukan `auto`. Setiap gambar manga melewati proxy dan menahan satu worker selama menunggu sumber. Dengan `auto` (= jumlah core, 4) halaman antre dan bab lambat dimuat. Satu worker ±30 MB RAM.

**`mancosync-reverb.service`**: `ExecStart=/usr/bin/php8.4 artisan reverb:start --host=127.0.0.1 --port=8087`, `User=www-data`, `WorkingDirectory=/var/www/html/manco-sync`, `Restart=always`.

**`mancosync-queue.service`**: `ExecStart=/usr/bin/php8.4 artisan queue:work --sleep=3 --tries=3 --max-time=3600`, sisanya sama seperti Reverb.

**`mancosync-rust.service`**

```ini
[Unit]
Description=MancoSync Rust API (sync engine)
After=network.target mongod.service

[Service]
User=www-data
WorkingDirectory=/var/www/html/manco-sync/manco-rust
Environment=DATABASE_URL=postgres://rend1:GANTI_PASSWORD_DB@127.0.0.1:5432/manco_sync
Environment=MONGODB_URI=mongodb://127.0.0.1:27017
Environment=REDIS_URL=redis://127.0.0.1:6381
Environment=PORT=8101
ExecStart=/var/www/html/manco-sync/manco-rust/target/release/manco-rust
Restart=always

[Install]
WantedBy=multi-user.target
```

Unit ini memuat password database, jadi batasi izinnya: `chmod 640 /etc/systemd/system/mancosync-rust.service`.

**`manco-anime-api.service`**: `WorkingDirectory=/var/www/manco-node/anime-api`, `Environment=PORT=8103`, `Environment=BIND_HOST=127.0.0.1`, `Environment=NODE_ENV=production`, `ExecStart=/usr/bin/node dist/index.js`, `User=www-data`, `Restart=always`.

**`manco-tmdb-embed.service`**: sama seperti di atas dengan `WorkingDirectory=/var/www/manco-node/tmdb-embed-api`, `PORT=8787`, `ExecStart=/usr/bin/node apiServer.js`.

**Timer** (masing-masing pasangan `.service` tipe `oneshot` dan `.timer`):

| Timer | Jadwal | Service menjalankan |
|---|---|---|
| `mancosync-schedule.timer` | `OnCalendar=*:0/1` | `/usr/bin/php8.4 artisan schedule:run` di folder aplikasi |
| `manco-portal-health.timer` | `OnCalendar=*-*-* *:*:00` | `/var/www/manco-node/portal-health.sh` (skrip yang memilih jam) |
| `manco-imgcache-clean.timer` | `OnCalendar=*-*-* 03:30:00`, `Persistent=true` | `find …/storage/app/imgcache -type f -mmin +10080 -delete` dan `find … -name '*.tmp' -mmin +60 -delete` |

Semua service memakai `User=www-data`.

**Aktifkan:**

```bash
systemctl daemon-reload
systemctl enable --now redis-beoulve mancosync-octane mancosync-reverb mancosync-queue mancosync-rust \
    manco-anime-api manco-tmdb-embed \
    mancosync-schedule.timer manco-portal-health.timer manco-imgcache-clean.timer
```

## 9. nginx

`/etc/nginx/sites-available/manco-sync-domain.conf`, lalu symlink ke `sites-enabled`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name manco.hustlesync.my.id www.manco.hustlesync.my.id;
    root /var/www/html/manco-sync/public;
    absolute_redirect off;
    port_in_redirect off;
    client_max_body_size 32M;
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/javascript application/json image/svg+xml;

    # Link lama berawalan /manco-sync tetap jalan
    location = /manco-sync { return 301 /; }
    location ^~ /manco-sync/ { rewrite ^/manco-sync/(.*)$ /$1 last; }

    location ^~ /.well-known/acme-challenge/ { default_type "text/plain"; try_files $uri =404; }

    # Reverb (WebSocket + API)
    location ^~ /app/mancosync_reverb_key_806 {
        proxy_pass http://127.0.0.1:8087;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }
    location ^~ /apps/806001 { proxy_pass http://127.0.0.1:8087; proxy_set_header Host $host; }

    location / { try_files $uri @octane; }
    location @octane {
        proxy_pass http://127.0.0.1:8047;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_read_timeout 60s;
    }
}
```

Path Reverb (`mancosync_reverb_key_806`, `806001`) mengikuti `REVERB_APP_KEY` dan `REVERB_APP_ID` di `.env`. Kalau keduanya diganti, sesuaikan juga di sini.

Server saat ini juga menyisipkan `include snippets/sec-headers.conf;` di `location /` dan `@octane`, serta ModSecurity dalam mode pantau. Keduanya opsional.

```bash
nginx -t && systemctl reload nginx
certbot --nginx -d manco.hustlesync.my.id -d www.manco.hustlesync.my.id
```

Minta **satu** sertifikat untuk kedua nama sekaligus (dua `-d` dalam satu perintah). Kalau diminta terpisah, sertifikat kedua menimpa yang pertama di server block yang sama dan domain tanpa `www` jadi error SSL.

## 10. Verifikasi

```bash
systemctl --failed
for p in 8047 8087 8101 8103 8787; do printf "%s " $p; curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:$p/; done
curl -sI https://manco.hustlesync.my.id/ | head -1
curl -s -o /dev/null -w 'portal %{http_code}\n' https://manco.hustlesync.my.id/portal/
tail -n 50 /var/www/html/manco-sync/storage/logs/laravel.log
```

Lalu di browser: buka beranda, satu bab komik, satu film (cek subtitle Indonesia), dan menu admin.

## 11. Catatan operasional (penting)

**Sanka API dan blokir IP.** Pada Oktober 2026 Sanka memblokir IPv6 dan IPv4 server ini ("permanently banned due to suspicious activity") karena volume permintaan. Akibatnya semua sumber komik berbasis Sanka gagal untuk bab yang belum ter-cache.

- Unban gratis lewat pengelola Sanka (https://t.me/OnlySankaaa). Sertakan domain dan IP server.
- Saat menerima balasan "banned", `SankaClient` berhenti memanggil Sanka selama 1 jam (cache key `sanka-banned`) dan hanya menyajikan cache. Ini disengaja. **Jangan** mengakalinya dengan memaksa IPv4, proxy, atau IP lain: blokir berikutnya akan menyusul.
- Agar tidak diblokir lagi: pemeriksa sumber hanya sekali sehari (§7), isi bab di-cache 7 hari (cadangan 30 hari), detail judul segar 10 menit (cadangan 7 hari). Hindari uji beban atau skrip berulang ke Sanka.

**Proxy gambar (`/portal/img`).**

- Cache disk di `storage/app/imgcache` (±1 GB, dibersihkan otomatis setelah 7 hari).
- Halaman besar dikompres ke JPEG progresif 72. JPEG dipilih karena WebP terlalu berat CPU saat 20+ halaman dikonversi bersamaan.
- Host komiku `image1`, `image3`, dan `image4` (jaringan 103.92.215.x) tidak terjangkau dari server ini, sehingga proxy mengambil berkas yang sama dari `image2.komiku.to` lebih dulu.

**Setelah mengubah kode:** `systemctl reload mancosync-octane`. Untuk view saja: `sudo -u www-data php8.4 artisan view:clear`. Jangan `route:cache`.

**Log:**

- `storage/logs/laravel.log`: error aplikasi dan kegagalan sumber.
- `storage/logs/portal-health.log`: hasil pemeriksa sumber.
- `journalctl -u <unit>`: tiap layanan.

**Pindah server, daftar salin:**

- database (`pg_dump` §3)
- `.env` (dengan `APP_KEY` yang sama)
- `storage/app/public`
- folder `/var/www/manco-node`
- unit systemd di §8
- config nginx §9
- `storage/app/imgcache` tidak wajib (hanya cache)

Ubah DNS terakhir, setelah semua layanan di server baru terverifikasi (§10).
