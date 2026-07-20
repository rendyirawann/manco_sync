# MancoSync — Panduan Deploy ke Server (Linux + systemd)

Panduan produksi untuk menjalankan **semua layanan** MancoSync di server Linux (Ubuntu 22.04/24.04),
dengan proses yang jalan di belakang layar via **systemd** dan Laravel dijalankan lewat **Octane (RoadRunner)**.

> Target: satu VPS/server. Semua perintah dijalankan sebagai user biasa `manco` (bukan root) kecuali yang diawali `sudo`.
> Ganti semua **`GANTI_*`** dan `yourdomain.com` dengan nilai asli kamu.

---

## 1. Arsitektur & peta port

| Layanan | Proses | Port (internal) | Publik? | Dijalankan oleh |
|---|---|---|---|---|
| **Nginx** | reverse proxy + TLS | 80 / 443 | ✅ publik | systemd (paket) |
| **Laravel (Octane/RoadRunner)** | web app | `127.0.0.1:8001` | via Nginx | `mancosync-octane.service` |
| **Reverb** | WebSocket realtime | `127.0.0.1:8080` | via Nginx (`/app`) | `mancosync-reverb.service` |
| **Queue worker** | antrian job | — | — | `mancosync-queue.service` |
| **Rust API** (`manco-rust`) | sync engine | `127.0.0.1:8000` | internal | `mancosync-rust.service` |
| **TMDB-Embed-API** (Node) | sumber stream film | `127.0.0.1:8787` | internal | `mancosync-embed.service` |
| **PostgreSQL** | database utama | `127.0.0.1:5432` | internal | paket `postgresql` |
| **Redis** | cache + session | `127.0.0.1:6379` | internal | paket `redis-server` |
| **MongoDB** | dipakai Rust engine | `127.0.0.1:27017` | internal | paket `mongod` |

**Alur:** Browser → Nginx (443) → Octane (8001). WebSocket → Nginx (`/app`) → Reverb (8080).
Laravel memanggil Embed-API (8787) & Rust (8000) **hanya dari dalam server** (localhost), jadi keduanya tidak
perlu dibuka ke internet.

> Catatan: di lokal Postgres pakai port **5433**; di server pakai default **5432**. Sesuaikan `.env`.

---

## 2. Prasyarat: install paket

```bash
# Update
sudo apt update && sudo apt upgrade -y

# PHP 8.2 + ekstensi yang dibutuhkan Laravel/Octane/RoadRunner
sudo apt install -y php8.2-cli php8.2-common php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd \
  php8.2-sockets

# Composer
sudo apt install -y composer   # atau install versi terbaru dari getcomposer.org

# Node.js 20 (untuk TMDB-Embed-API)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Rust (untuk manco-rust) — install untuk user manco
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y
source "$HOME/.cargo/env"

# Database & cache
sudo apt install -y postgresql redis-server

# MongoDB (repo resmi MongoDB 8.x)
curl -fsSL https://www.mongodb.org/static/pgp/server-8.0.asc | sudo gpg -o /usr/share/keyrings/mongodb-server-8.0.gpg --dearmor
echo "deb [ signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu $(lsb_release -cs)/mongodb-org/8.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-8.0.list
sudo apt update && sudo apt install -y mongodb-org

# Nginx + certbot (TLS)
sudo apt install -y nginx certbot python3-certbot-nginx git

# Aktifkan service database/cache
sudo systemctl enable --now postgresql redis-server mongod
```

> **Redis pakai `predis` (PHP murni)** — sudah jadi dependency Composer, jadi **tidak perlu** compile ekstensi `php-redis`.
> Cukup set `REDIS_CLIENT=predis` di `.env`.

---

## 3. Siapkan database PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE DATABASE manco_sync;
CREATE USER manco WITH PASSWORD 'GANTI_PASSWORD_DB';
GRANT ALL PRIVILEGES ON DATABASE manco_sync TO manco;
ALTER DATABASE manco_sync OWNER TO manco;
SQL
```

---

## 4. Ambil kode & konfigurasi

```bash
# Buat direktori & clone (sesuaikan URL repo kamu)
sudo mkdir -p /var/www && sudo chown manco:www-data /var/www
cd /var/www
git clone GANTI_URL_REPO manco-sync
cd manco-sync

# Dependency PHP (produksi, tanpa dev)
composer install --no-dev --optimize-autoloader

# Install binary RoadRunner untuk Octane (sekali saja)
php artisan octane:install --server=roadrunner
```

**TMDB-Embed-API** (repo terpisah — salin/clone ke server):

```bash
cd /var/www
git clone GANTI_URL_EMBED_API tmdb-embed-api   # atau upload folder tmdb-embed-api
cd tmdb-embed-api
npm ci --omit=dev            # atau: npm install --production
# buat .env untuk embed-api:
cat > .env <<'ENV'
TMDB_API_KEY=GANTI_TMDB_KEY
API_PORT=8787
ENABLE_PROXY=true
ENV
```

**Rust API** — build binary rilis:

```bash
cd /var/www/manco-sync/manco-rust
cargo build --release
# hasil: /var/www/manco-sync/manco-rust/target/release/manco-rust
```

---

## 5. File `.env` produksi (Laravel)

Buat `/var/www/manco-sync/.env`:

```dotenv
APP_NAME=MancoSync
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Jakarta

# Database (server pakai 5432, BUKAN 5433 seperti di lokal)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=manco_sync
DB_USERNAME=manco
DB_PASSWORD=GANTI_PASSWORD_DB

# Cache & session -> Redis via predis (tanpa ekstensi php-redis)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Broadcast (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=GANTI_APP_ID
REVERB_APP_KEY=GANTI_APP_KEY
REVERB_APP_SECRET=GANTI_APP_SECRET
# Server Reverb bind ke localhost (di belakang Nginx):
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
# Yang dipakai client/JS (lewat Nginx + TLS):
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

# Portal / API eksternal
SANKA_BASE=https://www.sankavollerei.web.id
SCRAPER_UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
TMDB_API_KEY=GANTI_TMDB_KEY
TMDB_EMBED_BASE=http://127.0.0.1:8787
OPENSUBTITLES_API_KEY=GANTI_OPENSUBTITLES_KEY
```

Lalu finalisasi:

```bash
cd /var/www/manco-sync
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Cache konfigurasi/route/view untuk performa (WAJIB di produksi)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Izin folder tulis
sudo chown -R manco:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

> **Penting:** karena `config:cache` dipakai, semua konfigurasi harus lewat `config()` (aplikasi ini sudah begitu).
> Setiap ubah `.env` di server → jalankan ulang `php artisan config:cache`.

---

## 6. File systemd (jalan di belakang layar)

Semua file di bawah dibuat dengan `sudo nano /etc/systemd/system/NAMA.service`.
Ganti `PASSWORD_DB` di unit Rust.

### 6.1 `mancosync-octane.service` — Laravel (Octane/RoadRunner)

```ini
[Unit]
Description=MancoSync Laravel (Octane/RoadRunner)
After=network.target postgresql.service redis-server.service

[Service]
Type=simple
User=manco
Group=www-data
WorkingDirectory=/var/www/manco-sync
ExecStart=/usr/bin/php artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8001 --workers=auto --max-requests=500
ExecReload=/usr/bin/php artisan octane:reload
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.2 `mancosync-reverb.service` — WebSocket

```ini
[Unit]
Description=MancoSync Reverb (WebSocket)
After=network.target

[Service]
Type=simple
User=manco
WorkingDirectory=/var/www/manco-sync
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.3 `mancosync-queue.service` — Queue worker

```ini
[Unit]
Description=MancoSync Queue Worker
After=network.target postgresql.service redis-server.service

[Service]
Type=simple
User=manco
WorkingDirectory=/var/www/manco-sync
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.4 `mancosync-rust.service` — Rust API

```ini
[Unit]
Description=MancoSync Rust API
After=network.target postgresql.service redis-server.service mongod.service

[Service]
Type=simple
User=manco
WorkingDirectory=/var/www/manco-sync/manco-rust
Environment=DATABASE_URL=postgres://manco:GANTI_PASSWORD_DB@127.0.0.1:5432/manco_sync
Environment=MONGODB_URI=mongodb://127.0.0.1:27017
Environment=REDIS_URL=redis://127.0.0.1:6379
Environment=PORT=8000
ExecStart=/var/www/manco-sync/manco-rust/target/release/manco-rust
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.5 `mancosync-embed.service` — TMDB Embed API (Node)

```ini
[Unit]
Description=MancoSync TMDB Embed API (stream film)
After=network.target

[Service]
Type=simple
User=manco
WorkingDirectory=/var/www/tmdb-embed-api
Environment=NODE_ENV=production
ExecStart=/usr/bin/node apiServer.js
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 6.6 Scheduler (cron)

```bash
crontab -e     # sebagai user manco
# tambahkan baris:
* * * * * cd /var/www/manco-sync && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 6.7 Aktifkan semua

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now \
  mancosync-octane mancosync-reverb mancosync-queue mancosync-rust mancosync-embed

# cek status
sudo systemctl status mancosync-octane --no-pager
```

---

## 7. Nginx (reverse proxy + WebSocket)

`sudo nano /etc/nginx/sites-available/mancosync`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    root /var/www/manco-sync/public;
    index index.php;
    client_max_body_size 50M;

    # WebSocket Reverb (client connect ke wss://yourdomain.com/app/...)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 3600s;
    }

    # Aset statis dilayani langsung; sisanya ke Octane
    location / {
        try_files $uri @octane;
    }
    location @octane {
        proxy_pass http://127.0.0.1:8001;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/mancosync /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# TLS (HTTPS) otomatis
sudo certbot --nginx -d yourdomain.com
```

> Alternatif WebSocket: pakai subdomain `ws.yourdomain.com` yang mem-proxy ke `:8080`, lalu set
> `REVERB_HOST=ws.yourdomain.com`. Cara `/app` di atas lebih simpel dan cukup untuk kebanyakan kasus.

---

## 8. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'      # 80 + 443
sudo ufw enable
# Port 8000/8080/8787/5432/6379/27017 TIDAK dibuka — semua bind ke 127.0.0.1 (aman, internal saja).
```

---

## 9. Update / redeploy (setiap ada perubahan kode)

Simpan sebagai `/var/www/manco-sync/deploy.sh`, lalu `chmod +x deploy.sh`:

```bash
#!/usr/bin/env bash
set -e
cd /var/www/manco-sync

php artisan down || true
git pull --ff-only
composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload Octane tanpa downtime (worker di-restart mulus)
php artisan octane:reload

# Restart layanan lain
sudo systemctl restart mancosync-reverb mancosync-queue

# Rust: build ulang HANYA kalau folder manco-rust berubah
# (cd manco-rust && cargo build --release) && sudo systemctl restart mancosync-rust

# Embed-API: kalau berubah
# (cd /var/www/tmdb-embed-api && git pull && npm ci --omit=dev) && sudo systemctl restart mancosync-embed

php artisan up
echo "✅ Deploy selesai."
```

> `sudo systemctl restart ...` di dalam script butuh izin sudo tanpa password untuk perintah tsb,
> atau jalankan `deploy.sh` dengan `sudo`. Cara aman: tambahkan aturan sudoers khusus (lihat §11).

---

## 10. Verifikasi & log

```bash
# Status semua layanan
systemctl status mancosync-octane mancosync-reverb mancosync-queue mancosync-rust mancosync-embed --no-pager

# Log real-time per layanan
journalctl -u mancosync-octane -f
journalctl -u mancosync-rust -f
journalctl -u mancosync-embed -f

# Cek port mendengarkan
ss -ltnp | grep -E ':(8001|8080|8000|8787|5432|6379|27017)'

# Uji dari server
curl -I http://127.0.0.1:8001            # Octane (Laravel)
curl -s http://127.0.0.1:8787/api/health # Embed-API
curl -s http://127.0.0.1:8000/           # Rust
```

Cek web: buka `https://yourdomain.com` → beranda portal muncul.
Uji film + subtitle, dan komik (reader loader).

---

## 11. Troubleshooting

- **502 Bad Gateway** → Octane belum jalan. `journalctl -u mancosync-octane -e`. Pastikan `octane:install` sudah download RoadRunner.
- **Perubahan `.env` tidak berefek** → jalankan `php artisan config:cache` lalu `php artisan octane:reload`.
- **Subtitle/portal lambat pertama kali** → normal (cache dingin sekali). Layanan `flexible` cache akan hangat sendiri; bisa dihangatkan dengan `curl -s https://yourdomain.com/portal`.
- **Rust gagal start** → cek `DATABASE_URL`/`MONGODB_URI`/`REDIS_URL` di unit-nya cocok dengan kredensial server; pastikan `mongod` jalan.
- **Redis error class not found** → pastikan `REDIS_CLIENT=predis` (predis sudah terpasang; ekstensi php-redis tidak wajib).
- **WebSocket gagal (Echo tak connect)** → cek blok Nginx `/app` (header `Upgrade`/`Connection`), dan `REVERB_HOST/PORT/SCHEME` client = domain publik + 443 + https.
- **Queue job diam** → `systemctl status mancosync-queue`; setelah deploy, worker perlu restart (script deploy sudah melakukannya).
- **sudo di deploy.sh minta password** → buat `/etc/sudoers.d/manco-deploy`:
  ```
  manco ALL=(root) NOPASSWD: /bin/systemctl restart mancosync-reverb, /bin/systemctl restart mancosync-queue, /bin/systemctl restart mancosync-rust, /bin/systemctl restart mancosync-embed
  ```

---

## 12. Ringkasan layanan

| systemd unit | Perintah inti | Auto-restart |
|---|---|---|
| `mancosync-octane` | `php artisan octane:start --server=roadrunner --port=8001` | ✅ |
| `mancosync-reverb` | `php artisan reverb:start --port=8080` | ✅ |
| `mancosync-queue` | `php artisan queue:work` | ✅ |
| `mancosync-rust` | `manco-rust` (binary rilis) | ✅ |
| `mancosync-embed` | `node apiServer.js` | ✅ |
| cron | `php artisan schedule:run` (tiap menit) | — |

Semua `enable --now` → otomatis hidup lagi saat server reboot. Selesai. 🚀
